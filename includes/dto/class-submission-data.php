<?php
/**
 * Submission DTO.
 *
 * Wraps a registration submission and provides convenient accessors.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Submission {

	/**
	 * Registration.
	 *
	 * @var object
	 */
	private $registration;

	/**
	 * Event.
	 *
	 * @var object|null
	 */
	private $event;

	/**
	 * Form schema.
	 *
	 * @var FormFlow_Form_Schema|null
	 */
	private $schema;

	/**
	 * Submitted data.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Constructor.
	 *
	 * @param object                    $registration Registration.
	 * @param object|null               $event        Event.
	 * @param FormFlow_Form_Schema|null $schema       Form schema.
	 */
	public function __construct(
		$registration,
		$event = null,
		$schema = null
	) {

		$this->registration = $registration;
		$this->event        = $event;
		$this->schema       = $schema;

		$this->data = json_decode(
			$registration->submission_data,
			true
		);

		if ( ! is_array( $this->data ) ) {
			$this->data = array();
		}
	}

	/**
	 * Get a field value.
	 *
	 * @param string $field   Field name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $field, $default = null ) {

		return $this->data[ $field ] ?? $default;
	}

	/**
	 * Determine whether a field exists.
	 *
	 * @param string $field Field name.
	 * @return bool
	 */
	public function has( $field ) {

		return isset( $this->data[ $field ] );
	}

	/**
	 * Get all fields.
	 *
	 * @return array
	 */
	public function all() {

		return $this->data;
	}

	/**
	 * Get registration.
	 *
	 * @return object
	 */
	public function registration() {

		return $this->registration;
	}

	/**
	 * Get event.
	 *
	 * @return object|null
	 */
	public function event() {

		return $this->event;
	}

	/**
	 * Get schema.
	 *
	 * @return FormFlow_Form_Schema|null
	 */
	public function schema() {

		return $this->schema;
	}

	/**
	 * Get a form field.
	 *
	 * @param string $name Field name.
	 * @return FormFlow_Form_Field|null
	 */
	public function field( $name ) {

		if ( ! $this->schema ) {
			return null;
		}

		return $this->schema->field( $name );
	}

	/**
	 * Convert to array.
	 *
	 * @return array
	 */
	public function to_array() {

		return $this->data;
	}

    /**
     * Get the submitter's email.
     *
     * @return string
     */
    public function email() {

        foreach ( $this->data as $key => $value ) {

            if ( false !== strpos( strtolower( $key ), 'email' ) ) {
                return is_array( $value ) ? '' : sanitize_email( $value );
            }
        }

        return '';
    }

    /**
     * Get the submitter's name.
     *
     * @return string
     */
    public function name() {

        $preferred = array(
            'your-name',
            'name',
            'full-name',
            'fullname',
        );

        foreach ( $preferred as $field ) {

            if ( ! empty( $this->data[ $field ] ) ) {
                return is_array( $this->data[ $field ] )
                    ? ''
                    : sanitize_text_field( $this->data[ $field ] );
            }
        }

        foreach ( $this->data as $key => $value ) {

            if ( false !== strpos( strtolower( $key ), 'name' ) ) {
                return is_array( $value )
                    ? ''
                    : sanitize_text_field( $value );
            }
        }

        return '';
    }

    /**
     * Get the submitter's phone number.
     *
     * @return string
     */
    public function phone() {

        $preferred = array(
            'phone',
            'tel',
            'your-phone',
            'your-tel',
            'mobile',
            'telephone',
        );

        foreach ( $preferred as $field ) {

            if ( ! empty( $this->data[ $field ] ) ) {
                return is_array( $this->data[ $field ] )
                    ? ''
                    : sanitize_text_field( $this->data[ $field ] );
            }
        }

        foreach ( $this->data as $key => $value ) {

            if (
                false !== strpos( strtolower( $key ), 'phone' ) ||
                false !== strpos( strtolower( $key ), 'tel' ) ||
                false !== strpos( strtolower( $key ), 'mobile' )
            ) {
                return is_array( $value )
                    ? ''
                    : sanitize_text_field( $value );
            }
        }

        return '';
    }

    /**
     * Get contact information.
     *
     * @return array
     */
    public function contact() {

        return array(
            'email' => $this->email(),
            'phone' => $this->phone(),
        );
    }
}