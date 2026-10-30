<?php
/**
 * Database Migrator.
 *
 * Runs database migrations when upgrading FormFlow.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Migrator {

	/**
	 * Run pending migrations.
	 *
	 * @return void
	 */
	public static function run() {

		$migrations = array(
			'1.1.0' => 'migration-1-1-0.php',
			'1.2.0' => 'migration-1-2-0.php',
			'1.2.1' => 'migration-1-2-1.php',
			// '1.3.0' => 'migration-1-3-0.php',
		);

		foreach ( $migrations as $version => $file ) {

			$path = FORMFLOW_PATH . 'includes/migrations/' . $file;

			if ( ! file_exists( $path ) ) {
				continue;
			}

			require_once $path;

			$class = 'FormFlow_Migration_' .
				str_replace(
					'.',
					'_',
					$version
				);

			if (
				class_exists( $class ) &&
				method_exists( $class, 'run' )
			) {
				$class::run();
			}
		}

		update_option(
			'formflow_version',
			FORMFLOW_VERSION
		);
	}
}