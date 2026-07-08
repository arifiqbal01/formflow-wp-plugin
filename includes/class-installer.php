<?php
/**
 * FormFlow Installer.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Installer {

	/**
	 * Activate plugin.
	 *
	 * @return void
	 */
	public static function activate() {

		self::create_tables();

		update_option(
			'formflow_version',
			FORMFLOW_VERSION
		);
	}

	/**
	 * Create or update database tables.
	 *
	 * dbDelta() safely creates or upgrades tables.
	 *
	 * @return void
	 */
	private static function create_tables() {

		require_once FORMFLOW_PATH . 'includes/database/class-events-table.php';
		require_once FORMFLOW_PATH . 'includes/database/class-event-batches-table.php';
		require_once FORMFLOW_PATH . 'includes/database/class-registrations-table.php';

		FormFlow_Events_Table::create();
		FormFlow_Event_Batches_Table::create();
		FormFlow_Registrations_Table::create();
	}
}