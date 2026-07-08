<?php
/**
 * Form Schema.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Form_Schema {

	/**
	 * Fields.
	 *
	 * @var FormFlow_Form_Field[]
	 */
	private $fields = array();

	public function add_field( FormFlow_Form_Field $field ) {

		$this->fields[ $field->name() ] = $field;
	}

	public function field( $name ) {

		return $this->fields[ $name ] ?? null;
	}

	public function fields() {

		return $this->fields;
	}
}