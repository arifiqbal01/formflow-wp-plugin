<?php
/** Payment schema upgrade. Existing attendees are never marked as paid. */
defined( 'ABSPATH' ) || exit;
class FormFlow_Migration_1_2_0 {
	public static function run() {
		if ( get_option( 'formflow_payment_schema' ) === '1.2.0' ) {
			return;
		}
		require_once FORMFLOW_PATH . 'includes/database/class-events-table.php';
		require_once FORMFLOW_PATH . 'includes/database/class-registrations-table.php';
		FormFlow_Events_Table::create();
		FormFlow_Registrations_Table::create();
		global $wpdb;
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}formflow_registrations", 0 );
		$status = $wpdb->get_row( "SHOW COLUMNS FROM {$wpdb->prefix}formflow_registrations LIKE 'status'" );
		$events = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}formflow_events", 0 );
		if ( in_array( 'confirmation_sent_at', $columns, true ) && in_array( 'pronamic_config_id', $events, true ) && $status && 'varchar(32)' === strtolower( $status->Type ) ) {
			update_option( 'formflow_payment_schema', '1.2.0' );
		}
	}
}
