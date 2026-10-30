<?php
/** Mandatory payment lifecycle. Pronamic owns provider callbacks; FormFlow owns admission. */
defined( 'ABSPATH' ) || exit;

use Pronamic\WordPress\Pay\Plugin as PronamicPlugin;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Customer;

class FormFlow_Payment_Service {
	private $database;
	private $capacity;
	private $email;

	public function __construct( FormFlow_Database $database, FormFlow_Capacity_Service $capacity, FormFlow_Email_Service $email ) {
		$this->database = $database;
		$this->capacity = $capacity;
		$this->email = $email;
		add_action( 'pronamic_payment_status_update_formflow', array( $this, 'sync_payment' ), 10, 1 );
		add_filter( 'pronamic_payment_redirect_url_formflow', array( $this, 'return_url' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'render_return' ) );
		add_action( 'formflow_payment_maintenance', array( $this, 'maintenance' ) );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		if ( ! wp_next_scheduled( 'formflow_payment_maintenance' ) ) {
			wp_schedule_event( time() + 300, 'formflow_five_minutes', 'formflow_payment_maintenance' );
		}
	}

	public function cron_schedules( $schedules ) {
		$schedules['formflow_five_minutes'] = array( 'interval' => 300, 'display' => __( 'Every five minutes', 'formflow' ) );
		return $schedules;
	}

	private function error( $message ) {
		return array( 'success' => false, 'message' => $message );
	}

	/** Reserve locally, then contact the provider outside the capacity lock. */
	public function begin( $event_id, array $data ) {
		if ( ! class_exists( PronamicPlugin::class ) || ! class_exists( Payment::class ) || ! function_exists( 'get_pronamic_payment' ) ) {
			return $this->error( __( 'Payment is currently unavailable. Please try again later.', 'formflow' ) );
		}
		$event = $this->database->events->get_event( $event_id );
		if ( ! $event || empty( $event->payment_required ) || ! $event->registration_open || (float) $event->payment_amount <= 0 ) {
			return $this->error( __( 'Registration is currently closed or its payment settings are incomplete.', 'formflow' ) );
		}
		$config_id = (int) ( $event->pronamic_config_id ?: get_option( 'pronamic_pay_config_id' ) );
		// Never silently fall back to another provider/configuration.
		if ( ! $config_id || 'publish' !== get_post_status( $config_id ) || 'mollie' !== get_post_meta( $config_id, '_pronamic_gateway_id', true ) || ! PronamicPlugin::get_gateway( $config_id ) ) {
			return $this->error( __( 'A published Mollie configuration is required. Please contact the organiser.', 'formflow' ) );
		}
		$dto = new FormFlow_Submission( (object) array( 'submission_data' => wp_json_encode( $data ) ), $event );
		if ( ! is_email( $dto->email() ) ) {
			return $this->error( __( 'A valid parent email address is required.', 'formflow' ) );
		}
		$result = $this->database->registrations->with_event_lock( $event_id, function () use ( $event_id, $data ) {
			$event = $this->database->events->get_event( $event_id );
			if ( ! $event || ! $event->registration_open || empty( $event->payment_required ) ) {
				return $this->error( __( 'Registration is currently closed.', 'formflow' ) );
			}
			$this->database->registrations->expire_reservations( $event_id );
			$batch = $this->database->batches->get_open_batch( $event_id );
			// Paid registration never silently becomes an unpaid waiting-list admission.
			if ( ! $batch || $this->capacity->is_full( $batch->id ) ) {
				return $this->error( __( 'No places are currently available. Please contact the organiser about the waiting list.', 'formflow' ) );
			}
			$id = $this->database->registrations->insert_registration( array(
				'event_id' => $event_id,
				'batch_id' => $batch->id,
				'status' => 'pending_payment',
				'submission_data' => wp_json_encode( $data ),
				'payment_status' => 'pending',
				'payment_amount' => $event->payment_amount,
				'payment_currency' => 'EUR',
				'reservation_expires_at' => gmdate( 'Y-m-d H:i:s', time() + 30 * MINUTE_IN_SECONDS ),
			) );
			return $id ? array( 'success' => true, 'registration_id' => $id, 'config_id' => (int) ( $event->pronamic_config_id ?: get_option( 'pronamic_pay_config_id' ) ) ) : $this->error( __( 'Unable to reserve a place. Please try again.', 'formflow' ) );
		} );
		if ( empty( $result['success'] ) ) {
			return $result;
		}
		$id = $result['registration_id'];
		$config_id = $result['config_id'];
		$row = $this->database->registrations->get_registration( $id );
		$payment = new Payment();
		try {
			if ( 'publish' !== get_post_status( $config_id ) || 'mollie' !== get_post_meta( $config_id, '_pronamic_gateway_id', true ) || ! PronamicPlugin::get_gateway( $config_id ) ) {
				throw new RuntimeException( 'Invalid Mollie configuration.' );
			}
			$payment->set_source( 'formflow' );
			$payment->set_source_id( (string) $id );
			$payment->set_config_id( $config_id );
			$payment->set_total_amount( new Money( $row->payment_amount, $row->payment_currency ) );
			$payment->set_description( sprintf( 'FormFlow #%d - %s', $id, $event->title ) );
			$payment->title = $payment->get_description();
			$customer = new Customer();
			$customer->set_email( $dto->email() );
			$payment->set_customer( $customer );
			// Save and bind before starting: a fast callback must already find its registration.
			$payment->save();
			if ( ! $payment->get_id() || ! $this->database->registrations->update_registration( $id, array( 'payment_id' => $payment->get_id() ) ) ) {
				throw new RuntimeException( 'Unable to bind payment.' );
			}
			$payment = PronamicPlugin::start_payment( $payment );
			$this->sync_payment( $payment );
			if ( in_array( $payment->get_status(), array( PaymentStatus::FAILURE, PaymentStatus::CANCELLED, PaymentStatus::EXPIRED ), true ) || ! $payment->get_transaction_id() ) {
				throw new RuntimeException( 'Unable to start payment.' );
			}
			return array( 'success' => true, 'status' => 'pending_payment', 'registration_id' => $id, 'redirect_url' => $payment->get_pay_redirect_url() );
		} catch ( Throwable $error ) {
			// Never overwrite an already successful callback, or release a hold for a payment
			// that may actually have started at Mollie after an ambiguous network timeout.
			$this->database->registrations->with_event_lock( $event_id, function () use ( $id, $payment ) {
				$current = $this->database->registrations->get_registration( $id );
				if ( $current && 'pending_payment' === $current->status && ! $payment->get_transaction_id() ) {
					$this->database->registrations->update_registration( $id, array( 'status' => 'cancelled', 'payment_status' => 'failed' ) );
				}
			} );
			return $this->error( __( 'Payment could not be started. Registration is not confirmed. Please try again or contact the organiser.', 'formflow' ) );
		}
	}

	/** Called only with Pronamic payment objects, never a browser-supplied success flag. */
	public function sync_payment( $payment ) {
		if ( 'formflow' !== $payment->get_source() ) {
			return;
		}
		$id = (int) $payment->get_source_id();
		$row = $this->database->registrations->get_registration( $id );
		if ( ! $row || (int) $row->payment_id !== (int) $payment->get_id() || ! $row->payment_id ) {
			return;
		}
		$result = $this->database->registrations->with_event_lock( $row->event_id, function () use ( $id, $payment ) {
			$row = $this->database->registrations->get_registration( $id );
			$status = $payment->get_status();
			if ( PaymentStatus::SUCCESS === $status ) {
				$total = $payment->get_total_amount();
				$matches = $total && $total->get_currency()->get_alphabetic_code() === $row->payment_currency && abs( (float) $total->get_value() - (float) $row->payment_amount ) < 0.005;
				if ( 'registered' === $row->status && 'paid' === $row->payment_status && $matches ) {
					return array( 'success' => true, 'confirm' => true );
				}
				if ( 'refunded' === $row->payment_status || ( 'paid' === $row->payment_status && 'cancelled' === $row->status ) || 'payment_review' === $row->status ) {
					return array( 'success' => true );
				}
				$batch = $this->database->batches->get_batch( $row->batch_id );
				$event = $this->database->events->get_event( $row->event_id );
				$live_hold = 'pending_payment' === $row->status && $row->reservation_expires_at > gmdate( 'Y-m-d H:i:s' );
				$can_admit = $event && $event->registration_open && $batch && 'open' === $batch->status &&
					( 'pending_payment' === $row->status || ( 'cancelled' === $row->status && in_array( $row->payment_status, array( 'expired', 'failed' ), true ) ) ) &&
					( $live_hold || ! $this->capacity->is_full( $batch->id ) );
				$admit = $matches && $can_admit;
				$updated = $this->database->registrations->update_registration( $id, array(
					'payment_status' => 'paid',
					'status' => $admit ? 'registered' : 'payment_review',
					'reservation_expires_at' => null,
				) );
				return array( 'success' => $updated, 'confirm' => $updated && $admit, 'review' => $updated && ! $admit );
			}
			$failed = array( PaymentStatus::FAILURE => 'failed', PaymentStatus::CANCELLED => 'cancelled', PaymentStatus::EXPIRED => 'expired', PaymentStatus::REFUNDED => 'refunded' );
			if ( isset( $failed[ $status ] ) ) {
				// Out-of-order failure updates cannot undo an already paid registration.
				if ( 'paid' === $row->payment_status && PaymentStatus::REFUNDED !== $status ) {
					return array( 'success' => true );
				}
				$this->database->registrations->update_registration( $id, array(
					'payment_status' => $failed[ $status ], 'status' => 'cancelled', 'reservation_expires_at' => null,
				) );
			}
			return array( 'success' => true );
		} );
		if ( ! empty( $result['confirm'] ) ) {
			$this->send_confirmation( $id );
		}
		if ( ! empty( $result['review'] ) ) {
			wp_mail( get_option( 'admin_email' ), __( 'FormFlow payment needs review', 'formflow' ), sprintf(
				/* translators: %d: registration ID */
				__( 'Payment for registration #%d was received, but admission could not be confirmed. Check the amount and available places in FormFlow and arrange admission or a refund through Pronamic/Mollie.', 'formflow' ), $id
			) );
		}
	}

	private function send_confirmation( $id ) {
		if ( ! $this->database->registrations->claim_confirmation( $id ) ) {
			return;
		}
		try {
			$sent = $this->email->send_registration_email( $id );
		} catch ( Throwable $error ) {
			$sent = false;
		}
		if ( ! $sent ) {
			$this->database->registrations->update_registration( $id, array( 'confirmation_sent_at' => null ) );
		}
	}

	public function maintenance() {
		if ( ! function_exists( 'get_pronamic_payment' ) ) {
			return;
		}
		foreach ( $this->database->registrations->payment_work() as $row ) {
			try {
				$payment = $row->payment_id ? get_pronamic_payment( $row->payment_id ) : null;
				if ( $payment ) {
					PronamicPlugin::update_payment( $payment, false );
					$this->sync_payment( $payment );
				}
			} catch ( Throwable $error ) {
				// Provider unavailable: future cron/webhook runs can reconcile it.
			}
			$this->database->registrations->with_event_lock( $row->event_id, function () use ( $row ) {
				$this->database->registrations->expire_reservations( $row->event_id );
			} );
		}
	}

	public function return_url( $url, $payment ) {
		return add_query_arg( array( 'formflow_payment' => $payment->get_id(), 'key' => $payment->key ), home_url( '/' ) );
	}

	/** Payment-key protected status page. No contact details or query-string success assertions. */
	public function render_return() {
		if ( empty( $_GET['formflow_payment'] ) || ! function_exists( 'get_pronamic_payment' ) ) {
			return;
		}
		$payment = get_pronamic_payment( absint( $_GET['formflow_payment'] ) );
		$key = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		if ( ! $payment || 'formflow' !== $payment->get_source() || ! $key || ! $payment->key || ! hash_equals( (string) $payment->key, $key ) ) {
			wp_die( esc_html__( 'Invalid payment link.', 'formflow' ), '', array( 'response' => 403 ) );
		}
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		try {
			PronamicPlugin::update_payment( $payment, false );
			$this->sync_payment( $payment );
		} catch ( Throwable $error ) {
			// Display stored status if provider lookup is temporarily unavailable.
		}
		$row = $this->database->registrations->get_registration( (int) $payment->get_source_id() );
		$message = __( 'Payment is still pending. Your registration is not confirmed. Refresh this page to check again.', 'formflow' );
		if ( $row && 'registered' === $row->status && 'paid' === $row->payment_status ) {
			$message = __( 'Payment successful. Your registration is confirmed. A confirmation email will be sent to you.', 'formflow' );
		} elseif ( $row && 'payment_review' === $row->status ) {
			$message = __( 'Your payment was received, but your place needs to be checked by the organiser. Your registration is not yet confirmed. Please contact us.', 'formflow' );
		} elseif ( $row && 'cancelled' === $row->status ) {
			$message = __( 'Payment was not completed or the reservation has expired. Your registration is not confirmed. Please return to the registration form or contact the organiser.', 'formflow' );
		}
		wp_die( esc_html( $message ), esc_html__( 'Registration payment', 'formflow' ), array( 'response' => 200 ) );
	}
}
