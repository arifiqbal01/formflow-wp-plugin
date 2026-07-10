<?php
/**
 * Events admin controller.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Events {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Event service.
	 *
	 * @var FormFlow_Event_Service
	 */
	private $event_service;

	/**
	 * Event batch service.
	 *
	 * @var FormFlow_Event_Batch_Service
	 */
	private $batch_service;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database            $database      Database facade.
	 * @param FormFlow_Event_Service       $event_service Event service.
	 * @param FormFlow_Event_Batch_Service $batch_service Event batch service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Event_Service $event_service,
		FormFlow_Event_Batch_Service $batch_service
	) {
		$this->database      = $database;
		$this->event_service = $event_service;
		$this->batch_service = $batch_service;
	}

	/**
	 * Render Events page.
	 *
	 * @return void
	 */
	public function render() {

		$this->handle_actions();

		$action = isset( $_GET['action'] )
			? sanitize_key( wp_unslash( $_GET['action'] ) )
			: '';

		$event = null;

		if ( 'edit' === $action && ! empty( $_GET['id'] ) ) {
			$event = $this->database->events->get_event(
				absint( $_GET['id'] )
			);
		}

		$contact_forms = WPCF7_ContactForm::find();

		$events = $this->database->events->get_events();

		require FORMFLOW_PATH . 'includes/admin/views/events-page.php';
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

		if ( 'delete' === $action ) {
			$this->delete_event();
		}

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$this->save_event();
		}
	}

	/**
	 * Save an event.
	 *
	 * @return void
	 */
	private function save_event() {

		if ( ! isset( $_POST['formflow_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['formflow_nonce'] ), 'formflow_save_event' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$event_id = isset( $_POST['event_id'] )
			? absint( $_POST['event_id'] )
			: 0;

		$data = array(
			'title'                     => sanitize_text_field( wp_unslash( $_POST['title'] ) ),
			'cf7_form_id'               => absint( $_POST['cf7_form_id'] ),
			'registration_open'         => isset( $_POST['registration_open'] ) ? 1 : 0,
			'waiting_list_enabled'      => isset( $_POST['waiting_list_enabled'] ) ? 1 : 0,
			'promotion_email_enabled'   => isset( $_POST['promotion_email_enabled'] ) ? 1 : 0,
		);

		if ( $event_id ) {

			$result  = $this->event_service->update(
				$event_id,
				$data
			);
			$message = 'updated=1';

		} else {

			$capacity = isset( $_POST['capacity'] )
				? max( 1, absint( $_POST['capacity'] ) )
				: 10;

			$result = $this->event_service->create(
				$data
			);

			if ( $result['success'] ) {

				$batch = $this->batch_service->create_first_batch(
					$result['event_id'],
					$capacity
				);

				if ( ! $batch['success'] ) {
					$result = $batch;
				}
			}

			$message = 'saved=1';
		}

		if ( ! $result['success'] ) {

			wp_safe_redirect(
				admin_url(
					'admin.php?page=formflow-events&error=' . rawurlencode( $result['message'] )
				)
			);

			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-events&' . $message
			)
		);

		exit;
	}

	/**
	 * Delete an event.
	 *
	 * @return void
	 */
	private function delete_event() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		check_admin_referer( 'formflow_delete_event' );

		$event_id = isset( $_GET['event'] )
			? absint( $_GET['event'] )
			: 0;

		if ( ! $event_id ) {
			return;
		}

		$result = $this->event_service->delete( $event_id );

		if ( ! $result['success'] ) {

			wp_safe_redirect(
				admin_url(
					'admin.php?page=formflow-events&error=' . rawurlencode( $result['message'] )
				)
			);

			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-events&deleted=1'
			)
		);

		exit;
	}
}