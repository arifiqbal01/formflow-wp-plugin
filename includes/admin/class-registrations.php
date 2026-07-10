<?php
/**
 * Registrations admin controller.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Registrations {

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
	 * Constructor.
	 *
	 * @param FormFlow_Database             $database Database facade.
	 * @param FormFlow_Registration_Service $registration_service Registration service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Registration_Service $registration_service
	) {
		$this->database             = $database;
		$this->registration_service = $registration_service;
	}

	/**
	 * Render the registrations page.
	 *
	 * @return void
	 */
	public function render() {
	
		$this->handle_actions();


		$registrations = $this->database
			->registrations
			->get_registrations();

		$submissions = array();

		foreach ( $registrations as $registration ) {

			$event = $this->database
				->events
				->get_event( $registration->event_id );

			$submission = new FormFlow_Submission(
				$registration,
				$event
			);

			$registration->open_batch = $this->database
				->batches
				->get_open_batch(
					$registration->event_id
				);

			$submissions[] = $submission;
		}

		require FORMFLOW_PATH . 'includes/admin/views/registrations-page.php';
	}

	/**
	 * Handle page actions.
	 *
	 * @return void
	 */
	private function handle_actions() {

		$action = isset( $_REQUEST['action'] )
			? sanitize_key( wp_unslash( $_REQUEST['action'] ) )
			: '';

		switch ( $action ) {

			case 'move_to_waiting':
				$this->move_to_waiting();
				break;

			case 'assign_to_batch':
				$this->assign_to_batch();
				break;

			case 'cancel_registration':
				$this->cancel_registration();
				break;
		}
	}

	/**
	 * Move a registration to the waiting list.
	 *
	 * @return void
	 */
	private function move_to_waiting() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'formflow_move_to_waiting' );

		$registration_id = isset( $_GET['registration'] )
			? absint( $_GET['registration'] )
			: 0;

		$result = $this->registration_service->move_to_waiting(
			$registration_id
		);

		$args = $result['success']
			? 'waiting=1'
			: 'error=' . rawurlencode( $result['message'] );

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-registrations&' . $args
			)
		);

		exit;
	}

	/**
	 * Assign a waiting registration to the current open batch.
	 *
	 * @return void
	 */
	private function assign_to_batch() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'formflow_assign_to_batch' );

		$registration_id = isset( $_GET['registration'] )
			? absint( $_GET['registration'] )
			: 0;

		$registration = $this->database
			->registrations
			->get_registration( $registration_id );

		if ( ! $registration ) {

			wp_safe_redirect(
				admin_url(
					'admin.php?page=formflow-registrations&error=registration_not_found'
				)
			);

			exit;
		}

		$batch = $this->database
			->batches
			->get_open_batch(
				$registration->event_id
			);

		if ( ! $batch ) {

			wp_safe_redirect(
				admin_url(
					'admin.php?page=formflow-registrations&error=no_open_batch'
				)
			);

			exit;
		}

		$result = $this->registration_service->assign_to_batch(
			$registration_id,
			$batch->id
		);

		$args = $result['success']
			? 'assigned=1'
			: 'error=' . rawurlencode( $result['message'] );

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-registrations&' . $args
			)
		);

		exit;
	}


	/**
	 * Cancel a registration.
	 *
	 * @return void
	 */
	private function cancel_registration() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'formflow_cancel_registration' );

		$registration_id = isset( $_GET['registration'] )
			? absint( $_GET['registration'] )
			: 0;

		$success = $this->registration_service->cancel(
			$registration_id
		);

		$args = $success
			? 'cancelled=1'
			: 'error=' . rawurlencode(
				__( 'Unable to cancel registration.', 'formflow' )
			);

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-registrations&' . $args
			)
		);

		exit;
	}
}