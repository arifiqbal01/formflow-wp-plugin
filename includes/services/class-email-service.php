<?php
/**
 * Email Service.
 *
 * Handles FormFlow email notifications.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Email_Service {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database $database Database facade.
	 */
	public function __construct( FormFlow_Database $database ) {
		$this->database = $database;
	}

	/**
	 * Send registration confirmation email.
	 *
	 * @param int $registration_id Registration ID.
	 * @return bool
	 */
	public function send_registration_email( $registration_id ) {

		error_log(
			sprintf(
				'[FormFlow] Het registratie-e-mailbericht voor registratie voorbereiden #%d',
				$registration_id
			)
		);

		$submission = $this->get_submission( $registration_id );

		if ( ! $submission ) {

			error_log(
				sprintf(
					'[FormFlow] Submission niet gevonden voor registratie #%d',
					$registration_id
				)
			);

			return false;
		}

		$email = sanitize_email(
			$submission->email()
		);

		if ( empty( $email ) ) {

			error_log(
				sprintf(
					'[FormFlow] Geen ontvangeremail voor registratie #%d',
					$registration_id
				)
			);

			return false;
		}


		$batch = null;

		if ( $submission->registration()->batch_id ) {

			$batch = $this->database
				->batches
				->get_batch(
					$submission->registration()->batch_id
				);
		}

		$subject = sprintf(
			__( 'Registration Confirmed - %s', 'formflow' ),
			$submission->event()->title
		);

		$message = $this->render_email(
			'emails/registration',
			array(
				'submission' => $submission,
				'event'      => $submission->event(),
				'batch'      => $batch,
			)
		);

		error_log(
			sprintf(
				'[FormFlow] Registration email size: %d bytes',
				strlen( $message )
			)
		);

		$sent = wp_mail(
			$email,
			$subject,
			$message,
			$this->headers()
		);

		error_log(
			sprintf(
				'[FormFlow] Registration email result: %s',
				$sent ? 'SUCCESS' : 'FAILED'
			)
		);

		return $sent;
	}

	/**
	 * Send waiting list email.
	 *
	 * @param int $registration_id Registration ID.
	 * @return bool
	 */
	public function send_waiting_email( $registration_id ) {

		error_log(
			sprintf(
				'[FormFlow] Preparing waiting email for registration #%d',
				$registration_id
			)
		);

		$submission = $this->get_submission( $registration_id );

		if ( ! $submission ) {

			error_log(
				sprintf(
					'[FormFlow] Submission not found for registration #%d',
					$registration_id
				)
			);

			return false;
		}

		$email = sanitize_email(
			$submission->email()
		);

		if ( empty( $email ) ) {

			error_log(
				sprintf(
					'[FormFlow] No recipient email for registration #%d',
					$registration_id
				)
			);

			return false;
		}


		$subject = sprintf(
			__( 'Waiting List - %s', 'formflow' ),
			$submission->event()->title
		);

		$message = $this->render_email(
			'emails/waiting-list',
			array(
				'submission' => $submission,
				'event'      => $submission->event(),
			)
		);

		error_log(
			sprintf(
				'[FormFlow] Waiting email size: %d bytes',
				strlen( $message )
			)
		);

		$sent = wp_mail(
			$email,
			$subject,
			$message,
			$this->headers()
		);

		error_log(
			sprintf(
				'[FormFlow] Waiting email result: %s',
				$sent ? 'SUCCESS' : 'FAILED'
			)
		);

		return $sent;
	}
	/**
	 * Send promotion email.
	 *
	 * @param int $registration_id Registration ID.
	 * @return bool
	 */
	public function send_promotion_email( $registration_id ) {

		error_log(
			sprintf(
				'[FormFlow] Preparing promotion email for registration #%d',
				$registration_id
			)
		);

		$submission = $this->get_submission( $registration_id );

		if ( ! $submission ) {

			error_log(
				sprintf(
					'[FormFlow] Submission not found for registration #%d',
					$registration_id
				)
			);

			return false;
		}

		$email = sanitize_email(
			$submission->email()
		);

		if ( empty( $email ) ) {

			error_log(
				sprintf(
					'[FormFlow] No recipient email for registration #%d',
					$registration_id
				)
			);

			return false;
		}


		$batch = $this->database
			->batches
			->get_batch(
				$submission->registration()->batch_id
			);

		$subject = sprintf(
			__( 'Registration Confirmed - %s', 'formflow' ),
			$submission->event()->title
		);

		$message = $this->render_email(
			'emails/promotion',
			array(
				'submission' => $submission,
				'event'      => $submission->event(),
				'batch'      => $batch,
			)
		);

		error_log(
			sprintf(
				'[FormFlow] Promotion email size: %d bytes',
				strlen( $message )
			)
		);

		$sent = wp_mail(
			$email,
			$subject,
			$message,
			$this->headers()
		);

		error_log(
			sprintf(
				'[FormFlow] Promotion email result: %s',
				$sent ? 'SUCCESS' : 'FAILED'
			)
		);

		return $sent;
	}

	/**
	 * Render a complete email.
	 *
	 * @param string $view Template path.
	 * @param array  $data Template data.
	 * @return string
	 */
	private function render_email(
		$view,
		array $data = array()
	) {

		$content = $this->render(
			$view,
			$data
		);

		return $this->render(
			'emails/layout',
			array(
				'content' => $content,
			)
		);
	}

	/**
	 * Render a template.
	 *
	 * @param string $template Template path.
	 * @param array  $data     Template variables.
	 * @return string
	 */
	private function render(
		$template,
		array $data = array()
	) {

		$file = FORMFLOW_PATH .
			'includes/templates/' .
			$template .
			'.php';

		if ( ! file_exists( $file ) ) {

			error_log(
				sprintf(
					'[FormFlow] Email template not found: %s',
					$file
				)
			);

			return '';
		}

		extract( $data, EXTR_SKIP );

		ob_start();

		require $file;

		$output = ob_get_clean();

		error_log(
			sprintf(
				'[FormFlow] Rendered template "%s" (%d bytes)',
				$template,
				strlen( $output )
			)
		);

		return $output;
	}


	/**
 * Email a payment invitation for an existing reserved registration.
 *
 * @param int    $registration_id Registration ID.
 * @param string $payment_url     Pronamic payment checkout URL.
 * @return bool Whether WordPress accepted the email for sending.
 */
public function send_payment_invitation(
	$registration_id,
	$payment_url
) {
	$submission = $this->get_submission( $registration_id );

	if ( ! $submission ) {
		return false;
	}

	$registration = $submission->registration();
	$event        = $submission->event();
	$email        = sanitize_email( $submission->email() );

	if (
		! is_email( $email ) ||
		'pending_payment' !== $registration->status ||
		empty( $registration->payment_id ) ||
		empty( $registration->reservation_expires_at ) ||
		(float) $registration->payment_amount <= 0
	) {
		return false;
	}

	$expires = strtotime(
		$registration->reservation_expires_at . ' UTC'
	);

	if ( false === $expires || $expires <= time() ) {
		return false;
	}

	$payment_url = esc_url_raw( $payment_url );

	// Pronamic checkout links should point to this WordPress website.
	if (
		! $payment_url ||
		wp_parse_url( $payment_url, PHP_URL_HOST ) !==
			wp_parse_url( home_url( '/' ), PHP_URL_HOST )
	) {
		return false;
	}

	$deadline = wp_date(
		get_option( 'date_format' ) . ' ' .
			get_option( 'time_format' ) . ' T',
		$expires,
		wp_timezone()
	);

	$amount = number_format_i18n(
		(float) $registration->payment_amount,
		2
	);

	$subject = sprintf(
		'Er is een plaats beschikbaar – %s',
		$event->title
	);

	$message = $this->render_email(
		'emails/payment-invitation',
		array(
			'event'       => $event,
			'amount'      => $amount,
			'currency'    => $registration->payment_currency,
			'deadline'    => $deadline,
			'payment_url' => $payment_url,
		)
	);

	return wp_mail(
		$email,
		$subject,
		$message,
		$this->headers()
	);
}

	/**
	 * Email headers.
	 *
	 * @return array
	 */
	private function headers() {

		return array(
			'Content-Type: text/html; charset=UTF-8',
		);
	}

	/**
	 * Get submission.
	 *
	 * @param int $registration_id Registration ID.
	 * @return FormFlow_Submission|null
	 */
	private function get_submission( $registration_id ) {

		$registration = $this->database
			->registrations
			->get_registration( $registration_id );

		if ( ! $registration ) {
			return null;
		}

		$event = $this->database
			->events
			->get_event( $registration->event_id );

		if ( ! $event ) {
			return null;
		}

		return new FormFlow_Submission(
			$registration,
			$event
		);
	}
}