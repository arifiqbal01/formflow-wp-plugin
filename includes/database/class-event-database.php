<?php
/**
 * Event database helper.
 *
 * Handles all event database operations.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Event_Database {

	/**
	 * Events table.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'formflow_events';
	}

	/**
	 * Insert an event.
	 *
	 * @param array $data Event data.
	 * @return int|false
	 */
	public function insert_event( array $data ) {
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
	 * Update an event.
	 *
	 * @param int   $event_id Event ID.
	 * @param array $data     Event data.
	 * @return bool
	 */
	public function update_event( $event_id, array $data ) {
		global $wpdb;

		return false !== $wpdb->update(
			$this->table,
			$data,
			array(
				'id' => $event_id,
			)
		);
	}

	/**
	 * Delete an event.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function delete_event( $event_id ) {
		global $wpdb;

		return false !== $wpdb->delete(
			$this->table,
			array(
				'id' => $event_id,
			)
		);
	}

	/**
	 * Get an event.
	 *
	 * @param int $event_id Event ID.
	 * @return object|null
	 */
	public function get_event( $event_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE id = %d",
				$event_id
			)
		);
	}

	/**
	 * Get an event by Contact Form 7 form ID.
	 *
	 * @param int $form_id Contact Form 7 form ID.
	 * @return object|null
	 */
	public function get_event_by_form_id( $form_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$this->table}
				WHERE cf7_form_id = %d
				LIMIT 1",
				$form_id
			)
		);
	}

	/**
	 * Check whether a Contact Form 7 form is already
	 * assigned to another event.
	 *
	 * Optionally exclude an event when editing.
	 *
	 * @param int $form_id          Contact Form 7 form ID.
	 * @param int $exclude_event_id Event ID to exclude.
	 * @return object|null
	 */
	public function get_event_by_form_id_excluding(
		$form_id,
		$exclude_event_id = 0
	) {
		global $wpdb;

		$sql = "
			SELECT *
			FROM {$this->table}
			WHERE cf7_form_id = %d
		";

		$args = array( $form_id );

		if ( $exclude_event_id ) {
			$sql .= " AND id != %d";
			$args[] = $exclude_event_id;
		}

		$sql .= " LIMIT 1";

		return $wpdb->get_row(
			$wpdb->prepare(
				$sql,
				$args
			)
		);
	}

	/**
	 * Get all events.
	 *
	 * @return array
	 */
	public function get_events() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT *
			FROM {$this->table}
			ORDER BY created_at DESC"
		);
	}

	/**
	 * Count events.
	 *
	 * @return int
	 */
	public function count_events() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$this->table}"
		);
	}
}