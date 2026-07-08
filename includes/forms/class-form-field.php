<?php
/**
 * Form Field.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Form_Field {

	/**
	 * Field name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Field label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Field type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Required.
	 *
	 * @var bool
	 */
	private $required;

	/**
	 * Options.
	 *
	 * @var array
	 */
	private $options;

	public function __construct(
		$name,
		$label,
		$type,
		$required = false,
		array $options = array()
	) {
		$this->name     = $name;
		$this->label    = $label;
		$this->type     = $type;
		$this->required = $required;
		$this->options  = $options;
	}

	public function name() {
		return $this->name;
	}

	public function label() {
		return $this->label;
	}

	public function type() {
		return $this->type;
	}

	public function required() {
		return $this->required;
	}

	public function options() {
		return $this->options;
	}
}