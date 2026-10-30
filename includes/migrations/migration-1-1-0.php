<?php
/**
 * Database migration for FormFlow 1.1.0.
 *
 * Adds new event configuration columns introduced in v1.1.0.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Migration_1_1_0 {

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public static function run() {

		global $wpdb;

		$table = $wpdb->prefix . 'formflow_events';

		// Table does not exist.
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);

		if ( $table !== $table_exists ) {
			return;
		}

		$columns = $wpdb->get_col(
			"SHOW COLUMNS FROM {$table}",
			0
		);

		// Registration open.
		if ( ! in_array( 'registration_open', $columns, true ) ) {

			$wpdb->query(
				"ALTER TABLE {$table}
				ADD COLUMN registration_open
				TINYINT(1) NOT NULL DEFAULT 1
				AFTER cf7_form_id"
			);
		}

		// Waiting list enabled.
		if ( ! in_array( 'waiting_list_enabled', $columns, true ) ) {

			$wpdb->query(
				"ALTER TABLE {$table}
				ADD COLUMN waiting_list_enabled
				TINYINT(1) NOT NULL DEFAULT 1
				AFTER registration_open"
			);
		}

		// Promotion email enabled.
		if ( ! in_array( 'promotion_email_enabled', $columns, true ) ) {

			$wpdb->query(
				"ALTER TABLE {$table}
				ADD COLUMN promotion_email_enabled
				TINYINT(1) NOT NULL DEFAULT 0
				AFTER waiting_list_enabled"
			);
		}
	}
}