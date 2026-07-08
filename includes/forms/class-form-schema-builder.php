<?php
/**
 * Form Schema Builder.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Form_Schema_Builder {

	/**
	 * Build schema from a CF7 form.
	 *
	 * @param int $form_id Form ID.
	 * @return FormFlow_Form_Schema
	 */
	public function build( $form_id ) {

		$schema = new FormFlow_Form_Schema();

		$form = WPCF7_ContactForm::find( $form_id );

		if ( ! $form ) {
			return $schema;
		}

		foreach ( $form->scan_form_tags() as $tag ) {

			$field = new FormFlow_Form_Field(
				$tag->name,
				ucwords( str_replace( '-', ' ', $tag->name ) ),
				$tag->basetype,
				$tag->is_required(),
				$tag->values
			);

			$schema->add_field( $field );
		}

		return $schema;
	}
}