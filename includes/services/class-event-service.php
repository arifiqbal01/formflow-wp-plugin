<?php
/**
 * Event Service.
 *
 * Handles event business logic.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Event_Service {

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
	public function __construct(
		FormFlow_Database $database
	) {
		$this->database = $database;
	}

	/**
	 * Validate event data.
	 *
	 * @param array $data Event data.
	 * @param int   $exclude_event_id Event ID to exclude.
	 * @return array
	 */
	private function validate(
		array $data,
		$exclude_event_id = 0
	) {

		if ( empty( $data['title'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Event title is required.', 'formflow' ),
			);
		}

		if ( empty( $data['cf7_form_id'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please select a Contact Form.', 'formflow' ),
			);
		}

		if ( ! empty( $data['payment_required'] ) && ( ! isset( $data['payment_amount'] ) || ! preg_match( '/^\d{1,8}(\.\d{1,2})?$/D', (string) $data['payment_amount'] ) || (float) $data['payment_amount'] <= 0 ) ) {
			return array( 'success' => false, 'message' => __( 'Enter a positive EUR amount with at most two decimal places.', 'formflow' ) );
		}

		$existing = $this->database->events->get_event_by_form_id_excluding(
			$data['cf7_form_id'],
			$exclude_event_id
		);

		if ( $existing ) {
			return array(
				'success' => false,
				'message' => __( 'This Contact Form is already assigned to another event.', 'formflow' ),
			);
		}

		return array(
			'success' => true,
		);
	}

	/**
	 * Create an event.
	 *
	 * @param array $data Event data.
	 * @return array
	 */
	public function create( array $data ) {

		$result = $this->validate( $data );

		if ( ! $result['success'] ) {
			return $result;
		}

		$event_id = $this->database->events->insert_event(
			$data
		);

		if ( ! $event_id ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to create event.', 'formflow' ),
			);
		}

		return array(
			'success'  => true,
			'event_id' => $event_id,
		);
	}

	/**
	 * Update an event.
	 *
	 * @param int   $event_id Event ID.
	 * @param array $data Event data.
	 * @return array
	 */
	public function update(
		$event_id,
		array $data
	) {

		$result = $this->validate(
			$data,
			$event_id
		);

		if ( ! $result['success'] ) {
			return $result;
		}

		$updated = $this->database->events->update_event(
			$event_id,
			$data
		);

		if ( ! $updated ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to update event.', 'formflow' ),
			);
		}

		return array(
			'success' => true,
		);
	}

	/**
	 * Delete an event.
	 *
	 * @param int $event_id Event ID.
	 * @return array
	 */
	public function delete( $event_id ) {

		if (
			$this->database
				->registrations
				->count_registrations( $event_id ) > 0
		) {
			return array(
				'success' => false,
				'message' => __( 'Events with registrations cannot be deleted.', 'formflow' ),
			);
		}

		$deleted = $this->database->events->delete_event(
			$event_id
		);

		if ( ! $deleted ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to delete event.', 'formflow' ),
			);
		}

		return array(
			'success' => true,
		);
	}
}