<?php
/**
 * Main plugin class.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Plugin {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init();
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		
	// Forms.
		require_once FORMFLOW_PATH . 'includes/forms/class-form-field.php';
		require_once FORMFLOW_PATH . 'includes/forms/class-form-schema.php';
		require_once FORMFLOW_PATH . 'includes/forms/class-form-schema-builder.php';

		// DTOs.
		require_once FORMFLOW_PATH . 'includes/dto/class-submission-data.php';


		// Loader.
		require_once FORMFLOW_PATH . 'includes/class-loader.php';

		// Installer / Migrations.
		require_once FORMFLOW_PATH . 'includes/migrations/class-migrator.php';

		// Database.
		require_once FORMFLOW_PATH . 'includes/database/class-event-database.php';
		require_once FORMFLOW_PATH . 'includes/database/class-event-batch-database.php';
		require_once FORMFLOW_PATH . 'includes/database/class-registration-database.php';
		require_once FORMFLOW_PATH . 'includes/database/class-database.php';

		// Services.
		require_once FORMFLOW_PATH . 'includes/services/class-capacity-service.php';
		require_once FORMFLOW_PATH . 'includes/services/class-registration-service.php';
		require_once FORMFLOW_PATH . 'includes/services/class-event-service.php';
		require_once FORMFLOW_PATH . 'includes/services/class-event-batch-service.php';
		require_once FORMFLOW_PATH . 'includes/services/class-email-service.php';

		require_once FORMFLOW_PATH . 'includes/services/class-payment-service.php';

		// Integrations.
		require_once FORMFLOW_PATH . 'includes/integrations/class-contact-form-7.php';

		// Admin.
		if ( is_admin() ) {
			require_once FORMFLOW_PATH . 'includes/admin/class-admin.php';
		}
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	private function init() {

		new FormFlow_Loader();

		// Shared database facade.
		$this->database = new FormFlow_Database();

		// Run database migrations.
		FormFlow_Migrator::run();


		// Shared services.
		$capacity_service = new FormFlow_Capacity_Service(
			$this->database
		);

		$email_service = new FormFlow_Email_Service(
			$this->database
		);

		$registration_service = new FormFlow_Registration_Service(
			$this->database,
			$capacity_service,
			$email_service
		);

		$batch_service = new FormFlow_Event_Batch_Service(
			$this->database,
			$registration_service,
			$capacity_service
		);

		$event_service = new FormFlow_Event_Service(
			$this->database
		);

		// Admin.
		if ( is_admin() ) {
			new FormFlow_Admin(
				$this->database,
				$event_service,
				$batch_service,
				$registration_service
			);
		}

		$payment_service = new FormFlow_Payment_Service( $this->database, $capacity_service, $email_service );

		// Contact Form 7 integration.
		if ( class_exists( 'WPCF7' ) ) {
			new FormFlow_Contact_Form_7(
                $this->database,
                $registration_service,
                $payment_service
            );
		}
	}

	public static function activate() {

		self::create_tables();

		update_option(
			'formflow_version',
			FORMFLOW_VERSION
		);
	}
}