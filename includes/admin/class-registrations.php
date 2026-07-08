<?php
/**
 * Registrations admin controller.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Registrations {

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
	 * Render the registrations page.
	 *
	 * @return void
	 */
	public function render() {

	$registrations = $this->database
		->registrations
		->get_registrations();

	$submissions = array();

	foreach ( $registrations as $registration ) {

		$event = $this->database
			->events
			->get_event( $registration->event_id );

		$submissions[] = new FormFlow_Submission(
			$registration,
			$event
		);
	}

	require FORMFLOW_PATH . 'includes/admin/views/registrations-page.php';
}
}