<?php
/**
 * Asset and hook loader.
 *
 * @package FormFlow
 */

defined( 'ABSPATH' ) || exit;

class FormFlow_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_assets' )
		);

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'enqueue_frontend_assets' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {

		if (
			false === strpos( $hook_suffix, 'formflow' ) &&
			'contact_page_formflow-events' !== $hook_suffix &&
			'contact_page_formflow-registrations' !== $hook_suffix
		) {
			return;
		}

		wp_enqueue_style(
			'formflow-admin',
			FORMFLOW_URL . 'assets/css/admin.css',
			array(),
			FORMFLOW_VERSION
		);

		wp_enqueue_script(
			'formflow-admin',
			FORMFLOW_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			FORMFLOW_VERSION,
			true
		);

		wp_localize_script(
			'formflow-admin',
			'formflow',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'formflow' ),
			)
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {

		// Future frontend CSS/JS.
	}
}