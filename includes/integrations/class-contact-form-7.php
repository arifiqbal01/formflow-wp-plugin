<?php
/**
 * Contact Form 7 integration.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Contact_Form_7 {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Registration service.
	 *
	 * @var FormFlow_Registration_Service
	 */
	private $registration_service;

	/**
	 * Registration result.
	 *
	 * @var array|null
	 */
	private $registration_result = null;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database             $database             Database facade.
	 * @param FormFlow_Registration_Service $registration_service Registration service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Registration_Service $registration_service
	) {

		$this->database             = $database;
		$this->registration_service = $registration_service;

		add_action(
			'wpcf7_before_send_mail',
			array( $this, 'handle_submission' ),
			10,
			1
		);

		add_filter(
			'wpcf7_feedback_response',
			array( $this, 'modify_feedback_response' ),
			10,
			2
		);
	}

	/**
	 * Handle Contact Form 7 submission.
	 *
	 * Only forms configured in FormFlow are processed.
	 *
	 * @param WPCF7_ContactForm $contact_form Contact Form 7 form.
	 * @return void
	 */
	public function handle_submission( $contact_form ) {

		$form_id = (int) $contact_form->id();

		// Is this Contact Form managed by FormFlow?
		$event = $this->database->events->get_event_by_form_id( $form_id );

		if ( ! $event ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return;
		}

		$posted_data = $submission->get_posted_data();

		$this->registration_result = $this->registration_service->register(
			$event->id,
			$posted_data
		);

		error_log(
			print_r( $this->registration_result, true )
		);

		$result = $this->registration_result;

		if ( ! $result['success'] ) {

			/**
			 * Future improvements:
			 *
			 * - Abort mail sending.
			 * - Show validation message.
			 * - Display registration closed/full message.
			 */
			return;
		}

		/**
		 * Future improvements:
		 *
		 * - Send registration confirmation email.
		 * - Send waiting list email.
		 * - Replace Contact Form 7 success message.
		 * - Fire custom actions.
		 */
	}

	/**
	 * Modify the Contact Form 7 success message for waiting list submissions.
	 *
	 * Validation and error messages are left unchanged.
	 *
	 * @param array $response CF7 response.
	 * @param array $result   Submission result.
	 * @return array
	 */
	public function modify_feedback_response( $response, $result ) {

		if ( empty( $this->registration_result ) ) {
			return $response;
		}

		/**
		 * Registration failed.
		 *
		 * Examples:
		 * - Event registration is closed.
		 * - No open batch and waiting list is disabled.
		 * - Batch is full and waiting list is disabled.
		 */
		if ( empty( $this->registration_result['success'] ) ) {

			$response['status'] = 'validation_failed';

			$response['message'] = ! empty( $this->registration_result['message'] )
				? $this->registration_result['message']
				: __( 'Aanmeldingen voor dit programma zijn momenteel gesloten.', 'formflow' );

			return $response;
		}

		/**
		 * Successfully added to waiting list.
		 */
		if (
			! empty( $this->registration_result['status'] ) &&
			'waiting' === $this->registration_result['status']
		) {

			$response['message'] = __(
				'Dank u! De huidige batch is vol, dus bent u toegevoegd aan de wachtlijst. We nemen contact met u op als er een plek beschikbaar is.',
				'formflow'
			);

			return $response;
		}

		return $response;
	}
}