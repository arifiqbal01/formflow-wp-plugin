<?php
/**
 * Event Batch database helper.
 *
 * Handles all event batch database operations.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Event_Batch_Database {

	/**
	 * Event batches table.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'formflow_event_batches';
	}

	/**
	 * Insert a batch.
	 *
	 * @param array $data Batch data.
	 * @return int|false
	 */
	public function insert_batch( array $data ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table,
			$data
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a batch.
	 *
	 * @param int   $batch_id Batch ID.
	 * @param array $data     Batch data.
	 * @return bool
	 */
	public function update_batch( $batch_id, array $data ) {
		global $wpdb;

		return false !== $wpdb->update(
			$this->table,
			$data,
			array( 'id' => $batch_id )
		);
	}

	/**
	 * Delete a batch.
	 *
	 * @param int $batch_id Batch ID.
	 * @return bool
	 */
	public function delete_batch( $batch_id ) {
		global $wpdb;

		return false !== $wpdb->delete(
			$this->table,
			array( 'id' => $batch_id )
		);
	}

	/**
	 * Get a batch.
	 *
	 * @param int $batch_id Batch ID.
	 * @return object|null
	 */
	public function get_batch( $batch_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE id = %d",
				$batch_id
			)
		);
	}

	/**
	 * Get all batches for an event.
	 *
	 * @param int $event_id Event ID.
	 * @return array
	 */
	public function get_batches_by_event( $event_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE event_id = %d
				ORDER BY batch_number ASC",
				$event_id
			)
		);
	}

	/**
	 * Get the currently open batch.
	 *
	 * @param int $event_id Event ID.
	 * @return object|null
	 */
	public function get_open_batch( $event_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE event_id = %d
				AND status = 'open'
				ORDER BY batch_number DESC
				LIMIT 1",
				$event_id
			)
		);
	}

	/**
	 * Get the latest batch.
	 *
	 * @param int $event_id Event ID.
	 * @return object|null
	 */
	public function get_latest_batch( $event_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE event_id = %d
				ORDER BY batch_number DESC
				LIMIT 1",
				$event_id
			)
		);
	}

	/**
	 * Get the next batch number.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function get_next_batch_number( $event_id ) {
		global $wpdb;

		$number = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(batch_number)
				FROM {$this->table}
				WHERE event_id = %d",
				$event_id
			)
		);

		return $number + 1;
	}

	/**
	 * Count batches for an event.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function count_batches( $event_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$this->table}
				WHERE event_id = %d",
				$event_id
			)
		);
	}

	/**
	 * Count open batches.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function count_open_batches( $event_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$this->table}
				WHERE event_id = %d
				AND status = 'open'",
				$event_id
			)
		);
	}
}