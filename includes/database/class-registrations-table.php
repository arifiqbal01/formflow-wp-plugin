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
            status VARCHAR(32) NOT NULL DEFAULT 'registered',
            submission_data LONGTEXT NOT NULL,
            payment_id BIGINT UNSIGNED DEFAULT NULL,
            payment_status VARCHAR(24) NOT NULL DEFAULT 'not_required',
            payment_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_currency CHAR(3) NOT NULL DEFAULT 'EUR',
            reservation_expires_at DATETIME DEFAULT NULL,
            confirmation_sent_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY batch_id (batch_id),
            KEY status (status),
            UNIQUE KEY payment_id (payment_id),
            KEY reservation_expires_at (reservation_expires_at)
        ) {$charset_collate};";

		dbDelta( $sql );
	}
}