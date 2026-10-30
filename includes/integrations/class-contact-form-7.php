<?php
/** CF7 adapter. Managed paid forms use FormFlow's checkout, not two competing payment handlers. */
defined( 'ABSPATH' ) || exit;

class FormFlow_Contact_Form_7 {
	private $database;
	private $registration_service;
	private $payments;
	private $registration_result;
	private $managed_form_id;
	private $paid_form = false;
	private $removed_callbacks = array();

	public function __construct( FormFlow_Database $database, FormFlow_Registration_Service $registration_service, FormFlow_Payment_Service $payments ) {
		$this->database = $database;
		$this->registration_service = $registration_service;
		$this->payments = $payments;
		add_action( 'wpcf7_before_send_mail', array( $this, 'handle_submission' ), 1, 3 );
		add_filter( 'wpcf7_skip_mail', array( $this, 'skip_mail' ), PHP_INT_MAX, 2 );
		add_filter( 'wpcf7_submission_result', array( $this, 'submission_result' ), PHP_INT_MAX, 2 );
		add_action( 'wpcf7_submit', array( $this, 'after_submit' ), 1, 2 );
		add_action( 'wpcf7_init', array( $this, 'register_tags' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function handle_submission( $contact_form, &$abort, $submission ) {
		$this->registration_result = null;
		$this->managed_form_id = null;
		$this->paid_form = false;
		$event = $this->database->events->get_event_by_form_id( (int) $contact_form->id() );
		if ( ! $event || $abort ) {
			return;
		}
		$this->managed_form_id = (int) $contact_form->id();
		$this->paid_form = ! empty( $event->payment_required );
		if ( $this->paid_form ) {
			$this->suspend_native_payment_handler();
			$this->registration_result = $this->payments->begin( $event->id, $submission->get_posted_data() );
			// No CF7 email (including Mail 2) and no normal "registration successful" result.
			$abort = true;
		} else {
			$this->registration_result = $this->registration_service->register( $event->id, $submission->get_posted_data() );
			if ( empty( $this->registration_result['success'] ) ) {
				$abort = true;
			}
		}
	}

	/** Scope removal to this submission, restoring callbacks on wpcf7_submit. */
	private function suspend_native_payment_handler() {
		global $wp_filter;
		if ( empty( $wp_filter['wpcf7_before_send_mail'] ) ) {
			return;
		}
		foreach ( $wp_filter['wpcf7_before_send_mail']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $entry ) {
				$callback = $entry['function'];
				if ( is_array( $callback ) && is_object( $callback[0] ) &&
					is_a( $callback[0], 'Pronamic\\WordPress\\Pay\\Extensions\\ContactForm7\\Extension' ) && 'before_send_mail' === $callback[1] ) {
					$this->removed_callbacks[] = array( $callback, $priority, $entry['accepted_args'] );
					remove_action( 'wpcf7_before_send_mail', $callback, $priority );
				}
			}
		}
	}

	public function skip_mail( $skip, $form ) {
		return ( $this->paid_form && (int) $form->id() === $this->managed_form_id ) ? true : $skip;
	}

	public function submission_result( $result, $submission ) {
	if (
		! $this->registration_result ||
		(int) $submission->get_contact_form()->id() !== $this->managed_form_id
	) {
		return $result;
	}

	if ( empty( $this->registration_result['success'] ) ) {
		$result['status'] = 'aborted';
		$result['message'] = $this->registration_result['message'];

		unset(
			$result['pronamic_pay_redirect_url'],
			$result['formflow_redirect_url']
		);
	} elseif (
		'waiting' === ( $this->registration_result['status'] ?? '' )
	) {
		// Successful waiting-list submission, not confirmed admission.
		$result['status'] = 'mail_sent';
		$result['message'] = __(
			'U staat op de wachtlijst. Zodra er een plaats beschikbaar is, ontvangt u bericht met verdere instructies. Uw inschrijving is pas definitief na betaling.',
			'formflow'
		);

		unset(
			$result['pronamic_pay_redirect_url'],
			$result['formflow_redirect_url']
		);
	} elseif ( $this->paid_form ) {
		$result['status'] = 'formflow_payment_redirect';
		$result['message'] = __(
			'Uw plaats is tijdelijk gereserveerd. Ga verder naar de betaling om uw inschrijving af te ronden.',
			'formflow'
		);
		$result['formflow_redirect_url'] =
			$this->registration_result['redirect_url'];
	}

	return $result;
}

	public function after_submit( $form, $result ) {
		foreach ( $this->removed_callbacks as $entry ) {
			add_action( 'wpcf7_before_send_mail', $entry[0], $entry[1], $entry[2] );
		}
		$this->removed_callbacks = array();
		$this->registration_result = null;
		$this->paid_form = false;
		if ( (int) $form->id() === $this->managed_form_id && ! empty( $result['formflow_redirect_url'] ) && function_exists( 'wpcf7_load_js' ) && ! wpcf7_load_js() ) {
			wp_safe_redirect( $result['formflow_redirect_url'] );
			exit;
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'formflow-checkout', FORMFLOW_URL . 'assets/js/checkout.js', array(), FORMFLOW_VERSION, true );
	}

	public function register_tags() {
		wpcf7_add_form_tag( 'formflow_payment_summary', array( $this, 'payment_summary' ) );
	}

	public function payment_summary() {
		$form = WPCF7_ContactForm::get_current();
		$event = $form ? $this->database->events->get_event_by_form_id( (int) $form->id() ) : null;
		if ( ! $event || empty( $event->payment_required ) ) {
			return '';
		}
		return '<p class="formflow-payment-summary">' . esc_html( sprintf(
			/* translators: %s: programme price in EUR */
			__( 'Programme fee: €%s. Payment is required to confirm your registration. Choose your payment method securely at Mollie checkout.', 'formflow' ),
			number_format_i18n( (float) $event->payment_amount, 2 )
		) ) . '</p>';
	}
}
