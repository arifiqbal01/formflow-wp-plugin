<?php
/**
 * Dashboard.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Dashboard {

	/**
	 * Database facade.
	 *
	 * @var FormFlow_Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param FormFlow_Database $database Database facade.
	 */
	public function __construct( FormFlow_Database $database ) {
		$this->database = $database;
	}

	/**
	 * Render dashboard page.
	 *
	 * @return void
	 */
	public function render() {

		$stats = array(
			'events'     => $this->database->events->count_events(),
			'registered' => $this->database->registrations->count_registrations_by_status( 'registered' ),
			'waiting'    => $this->database->registrations->count_registrations_by_status( 'waiting' ),
		);

		$events = $this->database
			->events
			->get_events();

		require FORMFLOW_PATH . 'includes/admin/views/dashboard-page.php';
	}
}