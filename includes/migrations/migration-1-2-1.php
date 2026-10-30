<?php
/** Reconcile batch schema with a dbDelta-compatible single-line ENUM. */
defined( 'ABSPATH' ) || exit;
class FormFlow_Migration_1_2_1 {
	public static function run() {
		if ( get_option( 'formflow_batch_schema' ) === '1.2.1' ) {
			return;
		}
		require_once FORMFLOW_PATH . 'includes/database/class-event-batches-table.php';
		FormFlow_Event_Batches_Table::create();
		global $wpdb;
		$status = $wpdb->get_row( "SHOW COLUMNS FROM {$wpdb->prefix}formflow_event_batches LIKE 'status'" );
		if ( $status && "enum('draft','open','closed','completed','cancelled')" === strtolower( $status->Type ) ) {
			update_option( 'formflow_batch_schema', '1.2.1' );
		}
	}
}
