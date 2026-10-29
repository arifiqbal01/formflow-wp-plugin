<?php
/**
 * Event Batch Service.
 *
 * Handles event batch business logic.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Event_Batch_Service {

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
	private $registrations;

	/**
	 * Capacity service.
	 *
	 * @var FormFlow_Capacity_Service
	 */
	private $capacity;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database             $database      Database facade.
	 * @param FormFlow_Registration_Service $registrations Registration service.
	 * @param FormFlow_Capacity_Service     $capacity      Capacity service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Registration_Service $registrations,
		FormFlow_Capacity_Service $capacity
	) {
		$this->database      = $database;
		$this->registrations = $registrations;
		$this->capacity      = $capacity;
	}

	/**
	 * Create the first batch for an event.
	 *
	 * @param int $event_id Event ID.
	 * @param int $capacity Capacity.
	 * @return array
	 */
	public function create_first_batch( $event_id, $capacity ) {

		if ( $this->database->batches->count_batches( $event_id ) > 0 ) {
			return array(
				'success' => false,
				'message' => __( 'The event already has a batch.', 'formflow' ),
			);
		}

		return $this->create_batch(
			$event_id,
			$capacity
		);
	}

	/**
	 * Create a batch.
	 *
	 * @param int $event_id Event ID.
	 * @param int $capacity Capacity.
	 * @return array
	 */
	public function create_batch( $event_id, $capacity ) {
		return $this->database->registrations->with_event_lock( $event_id, function () use ( $event_id, $capacity ) {
			return $this->create_batch_unlocked( $event_id, $capacity );
		} );
	}

	private function create_batch_unlocked( $event_id, $capacity ) {

		if ( $this->has_open_batch( $event_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Close the current batch before opening another.', 'formflow' ),
			);
		}

		$batch_id = $this->database
			->batches
			->insert_batch(
				array(
					'event_id'     => $event_id,
					'batch_number' => $this->database
						->batches
						->get_next_batch_number( $event_id ),
					'capacity'     => absint( $capacity ),
					'status'       => 'open',
				)
			);

		if ( ! $batch_id ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to create batch.', 'formflow' ),
			);
		}

		return array(
			'success'  => true,
			'batch_id' => $batch_id,
		);
	}

	/**
	 * Open the next batch.
	 *
	 * Creates the batch and automatically promotes
	 * waiting registrations until the batch is full.
	 *
	 * @param int $event_id Event ID.
	 * @param int $capacity Capacity.
	 * @return array
	 */
	public function open_next_batch(
		$event_id,
		$capacity
	) {

		$result = $this->create_batch(
			$event_id,
			$capacity
		);

		if ( ! $result['success'] ) {
			return $result;
		}

		$promoted = $this->registrations->promote_waiting_list(
			$event_id,
			$result['batch_id']
		);

		return array(
			'success'   => true,
			'batch_id'  => $result['batch_id'],
			'promoted'  => $promoted,
		);
	}

	/**
	 * Close a batch.
	 *
	 * @param int $batch_id Batch ID.
	 * @return bool
	 */
	public function close_batch( $batch_id ) {

		return $this->database
			->batches
			->update_batch(
				$batch_id,
				array(
					'status' => 'closed',
				)
			);
	}

	/**
	 * Complete a batch.
	 *
	 * @param int $batch_id Batch ID.
	 * @return bool
	 */
	public function complete_batch( $batch_id ) {

		return $this->database
			->batches
			->update_batch(
				$batch_id,
				array(
					'status' => 'completed',
				)
			);
	}

	/**
	 * Cancel a batch.
	 *
	 * @param int $batch_id Batch ID.
	 * @return bool
	 */
	public function cancel_batch( $batch_id ) {

		return $this->database
			->batches
			->update_batch(
				$batch_id,
				array(
					'status' => 'cancelled',
				)
			);
	}

	/**
	 * Update batch capacity.
	 *
	 * Capacity cannot be reduced below the number of
	 * attendees and active payment reservations. Completed and cancelled
	 * batches cannot be modified.
	 *
	 * @param int $batch_id Batch ID.
	 * @param int $capacity New capacity.
	 * @return array
	 */
	public function update_capacity(
		$batch_id,
		$capacity
	) {

		$batch = $this->database
			->batches
			->get_batch( $batch_id );

		if ( ! $batch ) {
			return array(
				'success' => false,
				'message' => __( 'Batch not found.', 'formflow' ),
			);
		}

		if (
			in_array(
				$batch->status,
				array(
					'completed',
					'cancelled',
				),
				true
			)
		) {
			return array(
				'success' => false,
				'message' => __( 'This batch can no longer be modified.', 'formflow' ),
			);
		}

		$capacity = max( 1, absint( $capacity ) );

		$registered = $this->capacity->occupied_count(
			$batch_id
		);

		if ( $capacity < $registered ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %d: Registered attendees. */
					__(
						'Capacity cannot be less than the %d attendees and active payment reservations.',
						'formflow'
					),
					$registered
				),
			);
		}

		$updated = $this->database
			->batches
			->update_batch(
				$batch_id,
				array(
					'capacity' => $capacity,
				)
			);

		if ( ! $updated ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to update batch capacity.', 'formflow' ),
			);
		}

		return array(
			'success' => true,
		);
	}
	

	/**
	 * Get the open batch.
	 *
	 * @param int $event_id Event ID.
	 * @return object|null
	 */
	public function get_open_batch( $event_id ) {

		return $this->database
			->batches
			->get_open_batch( $event_id );
	}

	/**
	 * Determine whether an event has an open batch.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function has_open_batch( $event_id ) {

		return null !== $this->get_open_batch( $event_id );
	}

	/**
	 * Get capacity information for a batch.
	 *
	 * @param int $batch_id Batch ID.
	 * @return array
	 */
	public function get_capacity( $batch_id ) {

		return $this->capacity->get_capacity( $batch_id );
	}

	/**
	 * Get waiting registration count.
	 *
	 * Waiting registrations belong to the event,
	 * not an individual batch.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function waiting_count( $event_id ) {

		return $this->capacity->waiting_count( $event_id );
	}
}