<?php
/**
 * Database facade.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Database {

	/**
	 * Event database.
	 *
	 * @var FormFlow_Event_Database
	 */
	public $events;

	/**
	 * Event batch database.
	 *
	 * @var FormFlow_Event_Batch_Database
	 */
	public $batches;

	/**
	 * Registration database.
	 *
	 * @var FormFlow_Registration_Database
	 */
	public $registrations;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->events = new FormFlow_Event_Database();

		$this->batches = new FormFlow_Event_Batch_Database();

		$this->registrations = new FormFlow_Registration_Database();
	}
}