<?php
/**
 * Capacity Service.
 *
 * Handles batch capacity logic.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Capacity_Service {

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
	 * Check whether a batch is full.
	 *
	 * @param int $batch_id Batch ID.
	 * @return bool
	 */
	public function is_full( $batch_id ) {

		$batch = $this->database->batches->get_batch( $batch_id );

		if ( ! $batch ) {
			return true;
		}

		return $this->registered_count( $batch_id ) >= (int) $batch->capacity;
	}

	/**
	 * Get registered count.
	 *
	 * @param int $batch_id Batch ID.
	 * @return int
	 */
	public function registered_count( $batch_id ) {

		return $this->database
			->registrations
			->count_batch_registrations(
				$batch_id,
				'registered'
			);
	}

	/**
	 * Get waiting count for an event.
	 *
	 * Waiting registrations are shared across the
	 * event, not a specific batch.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function waiting_count( $event_id ) {

		return $this->database
			->registrations
			->count_waiting( $event_id );
	}

	/**
	 * Get remaining seats.
	 *
	 * @param int $batch_id Batch ID.
	 * @return int
	 */
	public function remaining_seats( $batch_id ) {

		$batch = $this->database
			->batches
			->get_batch( $batch_id );

		if ( ! $batch ) {
			return 0;
		}

		$remaining = (int) $batch->capacity - $this->registered_count( $batch_id );

		return max( 0, $remaining );
	}

	/**
	 * Check whether seats are available.
	 *
	 * @param int $batch_id Batch ID.
	 * @return bool
	 */
	public function has_available_seats( $batch_id ) {

		return $this->remaining_seats( $batch_id ) > 0;
	}

	/**
	 * Get capacity information.
	 *
	 * @param int $batch_id Batch ID.
	 * @return array
	 */
	public function get_capacity( $batch_id ) {

		$batch = $this->database
			->batches
			->get_batch( $batch_id );

		if ( ! $batch ) {
			return array(
				'capacity'   => 0,
				'registered' => 0,
				'remaining'  => 0,
				'full'       => true,
			);
		}

		$registered = $this->registered_count( $batch_id );

		return array(
			'capacity'   => (int) $batch->capacity,
			'registered' => $registered,
			'remaining'  => max( 0, (int) $batch->capacity - $registered ),
			'full'       => $registered >= (int) $batch->capacity,
		);
	}
}