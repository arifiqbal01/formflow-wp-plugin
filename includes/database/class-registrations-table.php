<?php
/**
 * Registrations Table.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Registrations_Table {

	/**
	 * Create registrations table.
	 *
	 * @return void
	 */
	public static function create() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . 'formflow_registrations';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL,
            batch_id BIGINT UNSIGNED DEFAULT NULL,
            status ENUM(
                'registered',
                'waiting',
                'cancelled'
            ) NOT NULL DEFAULT 'registered',
            submission_data LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY batch_id (batch_id),
            KEY status (status)
        ) {$charset_collate};";

		dbDelta( $sql );
	}
}