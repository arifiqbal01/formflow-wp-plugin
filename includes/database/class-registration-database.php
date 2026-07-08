<?php
/**
 * Registration database helper.
 *
 * Handles all registration database operations.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Registration_Database {

	/**
	 * Registrations table.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Events table.
	 *
	 * @var string
	 */
	private $events_table;

	/**
	 * Event batches table.
	 *
	 * @var string
	 */
	private $batches_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table         = $wpdb->prefix . 'formflow_registrations';
		$this->events_table  = $wpdb->prefix . 'formflow_events';
		$this->batches_table = $wpdb->prefix . 'formflow_event_batches';
	}

	/**
	 * Insert a registration.
	 *
	 * @param array $data Registration data.
	 * @return int|false
	 */
	public function insert_registration( array $data ) {
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
	 * Update a registration.
	 *
	 * @param int   $id   Registration ID.
	 * @param array $data Registration data.
	 * @return bool
	 */
	public function update_registration( $id, array $data ) {
		global $wpdb;

		return false !== $wpdb->update(
			$this->table,
			$data,
			array(
				'id' => $id,
			)
		);
	}

	/**
	 * Delete a registration.
	 *
	 * @param int $id Registration ID.
	 * @return bool
	 */
	public function delete_registration( $id ) {
		global $wpdb;

		return false !== $wpdb->delete(
			$this->table,
			array(
				'id' => $id,
			)
		);
	}

	/**
	 * Get a registration.
	 *
	 * @param int $id Registration ID.
	 * @return object|null
	 */
	public function get_registration( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Get registrations.
	 *
	 * @param int|null $event_id Event ID.
	 * @param int|null $batch_id Batch ID.
	 * @return array
	 */
	public function get_registrations(
		$event_id = null,
		$batch_id = null
	) {
		global $wpdb;

		$sql = "
			SELECT
				r.*,
				e.title AS event_title,
				b.batch_number
			FROM {$this->table} r
			LEFT JOIN {$this->events_table} e
				ON e.id = r.event_id
			LEFT JOIN {$this->batches_table} b
				ON b.id = r.batch_id
			WHERE 1 = 1
		";

		$args = array();

		if ( null !== $event_id ) {
			$sql .= " AND r.event_id = %d";
			$args[] = $event_id;
		}

		if ( null !== $batch_id ) {
			$sql .= " AND r.batch_id = %d";
			$args[] = $batch_id;
		}

		$sql .= " ORDER BY r.created_at DESC";

		if ( empty( $args ) ) {
			return $wpdb->get_results( $sql );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				$args
			)
		);
	}

	/**
	 * Get waiting registrations.
	 *
	 * @param int $event_id Event ID.
	 * @param int $limit    Maximum number of records.
	 * @return array
	 */
	public function get_waiting_registrations(
		$event_id,
		$limit = 0
	) {
		global $wpdb;

		$sql = "
			SELECT *
			FROM {$this->table}
			WHERE event_id = %d
			AND status = 'waiting'
			ORDER BY created_at ASC
		";

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare(
				' LIMIT %d',
				$limit
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				$event_id
			)
		);
	}

	/**
	 * Count registrations.
	 *
	 * @param int         $event_id Event ID.
	 * @param int|null    $batch_id Batch ID.
	 * @param string|null $status   Registration status.
	 * @return int
	 */
	public function count_registrations(
		$event_id,
		$batch_id = null,
		$status = null
	) {
		global $wpdb;

		$sql = "
			SELECT COUNT(*)
			FROM {$this->table}
			WHERE event_id = %d
		";

		$args = array( $event_id );

		if ( null !== $batch_id ) {
			$sql .= " AND batch_id = %d";
			$args[] = $batch_id;
		}

		if ( null !== $status ) {
			$sql .= " AND status = %s";
			$args[] = $status;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				$sql,
				$args
			)
		);
	}

	/**
	 * Count registrations for a batch.
	 *
	 * @param int         $batch_id Batch ID.
	 * @param string|null $status   Registration status.
	 * @return int
	 */
	public function count_batch_registrations(
		$batch_id,
		$status = null
	) {
		global $wpdb;

		$sql = "
			SELECT COUNT(*)
			FROM {$this->table}
			WHERE batch_id = %d
		";

		$args = array( $batch_id );

		if ( null !== $status ) {
			$sql .= " AND status = %s";
			$args[] = $status;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				$sql,
				$args
			)
		);
	}

	/**
	 * Count waiting registrations.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function count_waiting( $event_id ) {
		return $this->count_registrations(
			$event_id,
			null,
			'waiting'
		);
	}

	/**
	 * Move a registration to a batch.
	 *
	 * @param int $registration_id Registration ID.
	 * @param int $batch_id        Batch ID.
	 * @return bool
	 */
	public function move_to_batch(
		$registration_id,
		$batch_id
	) {
		return $this->update_registration(
			$registration_id,
			array(
				'batch_id' => $batch_id,
				'status'   => 'registered',
			)
		);
	}

	/**
	 * Assign multiple registrations to a batch.
	 *
	 * @param array $registration_ids Registration IDs.
	 * @param int   $batch_id         Batch ID.
	 * @return void
	 */
	public function assign_batch(
		array $registration_ids,
		$batch_id
	) {
		foreach ( $registration_ids as $registration_id ) {
			$this->move_to_batch(
				$registration_id,
				$batch_id
			);
		}
	}

	/**
	 * Count all registrations.
	 *
	 * @return int
	 */
	public function count_all_registrations() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$this->table}"
		);
	}

	/**
	 * Count registrations by status.
	 *
	 * @param string $status Registration status.
	 * @return int
	 */
	public function count_registrations_by_status( $status ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$this->table}
				WHERE status = %s",
				$status
			)
		);
	}
}