<?php
/**
 * Event Batches Table.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Event_Batches_Table {

	/**
	 * Create batches table.
	 *
	 * @return void
	 */
	public static function create() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . 'formflow_event_batches';

		$charset_collate = $wpdb->get_charset_collate();

		// dbDelta requires each complete column definition on one line.
		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id BIGINT UNSIGNED NOT NULL,
			batch_number INT UNSIGNED NOT NULL DEFAULT 1,
			capacity INT UNSIGNED NOT NULL DEFAULT 10,
			status ENUM('draft','open','closed','completed','cancelled') NOT NULL DEFAULT 'open',
			starts_at DATETIME NULL,
			ends_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_id (event_id),
			UNIQUE KEY event_batch (event_id, batch_number)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}