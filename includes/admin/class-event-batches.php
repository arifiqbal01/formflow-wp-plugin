<?php
/**
 * Event Batches admin controller.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Event_Batches {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Batch service.
	 *
	 * @var FormFlow_Event_Batch_Service
	 */
	private $batch_service;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database            $database Database.
	 * @param FormFlow_Event_Batch_Service $batch_service Batch service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Event_Batch_Service $batch_service
	) {

		$this->database      = $database;
		$this->batch_service = $batch_service;
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render() {

		$this->handle_actions();

		$event_id = isset( $_GET['event'] )
			? absint( $_GET['event'] )
			: 0;

		if ( ! $event_id ) {
			wp_die( esc_html__( 'Invalid event.', 'formflow' ) );
		}

		$event = $this->database->events->get_event( $event_id );

		if ( ! $event ) {
			wp_die( esc_html__( 'Event not found.', 'formflow' ) );
		}

		$batches = $this->database
			->batches
			->get_batches_by_event( $event_id );

		$open_batch = $this->database
			->batches
			->get_open_batch( $event_id );

		// Capacity summary for the current open batch.
		$capacity = array(
			'capacity'   => 0,
			'registered' => 0,
			'remaining'  => 0,
			'full'       => true,
		);

		if ( $open_batch ) {
			$capacity = $this->batch_service->get_capacity(
				$open_batch->id
			);
		}

		// Waiting registrations for this event.
		$waiting = $this->batch_service->waiting_count(
			$event_id
		);

		require FORMFLOW_PATH . 'includes/admin/views/event-batches-page.php';
	}

	/**
	 * Handle actions.
	 *
	 * @return void
	 */
	private function handle_actions() {

		$action = isset( $_REQUEST['action'] )
			? sanitize_key( wp_unslash( $_REQUEST['action'] ) )
			: '';

		switch ( $action ) {

			case 'create':
				$this->create_batch();
				break;
			
			case 'update-capacity':
				$this->update_capacity();
				break;

			case 'close':
				$this->close_batch();
				break;

			case 'complete':
				$this->complete_batch();
				break;

			case 'cancel':
				$this->cancel_batch();
				break;
		}
	}

	/**
	 * Create batch.
	 *
	 * @return void
	 */
	private function create_batch() {

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if (
			! isset( $_POST['formflow_nonce'] ) ||
			! wp_verify_nonce(
				wp_unslash( $_POST['formflow_nonce'] ),
				'formflow_create_batch'
			)
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$event_id = absint( $_POST['event_id'] );

		$capacity = absint( $_POST['capacity'] );

		$result = $this->batch_service->open_next_batch(
			$event_id,
			$capacity
		);

		if ( ! $result['success'] ) {

			wp_safe_redirect(
				admin_url(
					'admin.php?page=formflow-event-batches&event=' .
					$event_id .
					'&error=' .
					rawurlencode( $result['message'] )
				)
			);

			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-event-batches&event=' .
				$event_id .
				'&created=1'
			)
		);

		exit;
	}

	/**
	 * Update batch capacity.
	 *
	 * @return void
	 */
	private function update_capacity() {

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if (
			! isset( $_POST['formflow_nonce'] ) ||
			! wp_verify_nonce(
				wp_unslash( $_POST['formflow_nonce'] ),
				'formflow_update_capacity'
			)
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$batch_id = absint( $_POST['batch_id'] );
		$event_id = absint( $_POST['event_id'] );
		$capacity = absint( $_POST['capacity'] );

		$result = $this->batch_service->update_capacity(
			$batch_id,
			$capacity
		);

		if ( ! $result['success'] ) {

			wp_safe_redirect(
				admin_url(
					'admin.php?page=formflow-event-batches&event=' .
					$event_id .
					'&error=' .
					rawurlencode( $result['message'] )
				)
			);

			exit;
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-event-batches&event=' .
				$event_id .
				'&capacity-updated=1'
			)
		);

		exit;
	}

	/**
	 * Close batch.
	 *
	 * @return void
	 */
	private function close_batch() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'formflow_close_batch' );

		$batch_id = absint( $_GET['batch'] );
		$event_id = absint( $_GET['event'] );

		$this->batch_service->close_batch( $batch_id );

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-event-batches&event=' .
				$event_id .
				'&closed=1'
			)
		);

		exit;
	}

	/**
	 * Complete batch.
	 *
	 * @return void
	 */
	private function complete_batch() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'formflow_complete_batch' );

		$batch_id = absint( $_GET['batch'] );
		$event_id = absint( $_GET['event'] );

		$this->batch_service->complete_batch( $batch_id );

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-event-batches&event=' .
				$event_id .
				'&completed=1'
			)
		);

		exit;
	}

	/**
	 * Cancel batch.
	 *
	 * @return void
	 */
	private function cancel_batch() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'formflow_cancel_batch' );

		$batch_id = absint( $_GET['batch'] );
		$event_id = absint( $_GET['event'] );

		$this->batch_service->cancel_batch( $batch_id );

		wp_safe_redirect(
			admin_url(
				'admin.php?page=formflow-event-batches&event=' .
				$event_id .
				'&cancelled=1'
			)
		);

		exit;
	}
}