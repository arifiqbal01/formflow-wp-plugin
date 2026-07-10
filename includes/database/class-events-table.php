<?php
/**
 * Events Table.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Events_Table {

	/**
	 * Create events table.
	 *
	 * @return void
	 */
	public static function create() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . 'formflow_events';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			cf7_form_id BIGINT UNSIGNED NOT NULL,
			waiting_list_enabled TINYINT(1) NOT NULL DEFAULT 1,
			promotion_email_enabled TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY cf7_form_id (cf7_form_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}