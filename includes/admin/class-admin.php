<?php
/**
 * Admin functionality.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Admin {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Event service.
	 *
	 * @var FormFlow_Event_Service
	 */
	private $event_service;

	/**
	 * Event batch service.
	 *
	 * @var FormFlow_Event_Batch_Service
	 */
	private $batch_service;

	/**
	 * Registration service.
	 *
	 * @var FormFlow_Registration_Service
	 */
	private $registration_service;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database             $database             Database facade.
	 * @param FormFlow_Event_Service        $event_service        Event service.
	 * @param FormFlow_Event_Batch_Service  $batch_service        Batch service.
	 * @param FormFlow_Registration_Service $registration_service Registration service.
	 */
	public function __construct(
		FormFlow_Database $database,
		FormFlow_Event_Service $event_service,
		FormFlow_Event_Batch_Service $batch_service,
		FormFlow_Registration_Service $registration_service
	) {

		$this->database      = $database;
		$this->event_service = $event_service;
		$this->batch_service = $batch_service;
		$this->registration_service = $registration_service;

		add_action(
			'admin_menu',
			array( $this, 'register_menu' )
		);
	}

	/**
	 * Register the FormFlow admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {

		// Only add the menu if Contact Form 7 is active.
		if ( ! class_exists( 'WPCF7' ) ) {
			return;
		}

		// Dashboard.
		add_submenu_page(
			'wpcf7',
			__( 'FormFlow Dashboard', 'formflow' ),
			__( 'FormFlow', 'formflow' ),
			'manage_options',
			'formflow',
			array( $this, 'render_page' )
		);

		// Events.
		add_submenu_page(
			'wpcf7',
			__( 'Events', 'formflow' ),
			__( 'Events', 'formflow' ),
			'manage_options',
			'formflow-events',
			array( $this, 'render_events_page' )
		);

        add_submenu_page(
            null,
            __( 'Event Batches', 'formflow' ),
            __( 'Event Batches', 'formflow' ),
            'manage_options',
            'formflow-event-batches',
            array( $this, 'render_batches_page' )
        );

		// Registrations.
		add_submenu_page(
			'wpcf7',
			__( 'Registrations', 'formflow' ),
			__( 'Registrations', 'formflow' ),
			'manage_options',
			'formflow-registrations',
			array( $this, 'render_registrations_page' )
		);
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function render_page() {

		require_once FORMFLOW_PATH . 'includes/admin/class-dashboard.php';

		$page = new FormFlow_Dashboard(
			$this->database
		);

		$page->render();
	}

	/**
	 * Render events page.
	 *
	 * @return void
	 */
	public function render_events_page() {

		require_once FORMFLOW_PATH . 'includes/admin/class-events.php';

		$page = new FormFlow_Events(
			$this->database,
			$this->event_service,
			$this->batch_service
		);

		$page->render();
	}

    public function render_batches_page() {

        require_once FORMFLOW_PATH . 'includes/admin/class-event-batches.php';

        $page = new FormFlow_Event_Batches(
            $this->database,
            $this->batch_service
        );

        $page->render();
    }

	/**
	 * Render registrations page.
	 *
	 * @return void
	 */
	public function render_registrations_page() {

		require_once FORMFLOW_PATH . 'includes/admin/class-registrations.php';

		$page = new FormFlow_Registrations(
			$this->database,
			$this->registration_service
		);

		$page->render();
	}
}