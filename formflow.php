<?php
/**
 * Plugin Name: FormFlow
 * Description: Registration and waiting list management for Contact Form 7.
 * Version: 1.0.0
 * Author: WebArtsy
 * Author URI: https://webartsy.nl
 * Text Domain: formflow
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// Plugin version.
define( 'FORMFLOW_VERSION', '1.0.0' );

// Plugin paths.
define( 'FORMFLOW_FILE', __FILE__ );
define( 'FORMFLOW_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORMFLOW_URL', plugin_dir_url( __FILE__ ) );

// Core classes.
require_once FORMFLOW_PATH . 'includes/class-installer.php';

/**
 * Check whether Contact Form 7 is active.
 *
 * @return bool
 */
function formflow_is_cf7_active() {
	return class_exists( 'WPCF7' );
}

// Activation.
register_activation_hook(
	FORMFLOW_FILE,
	'formflow_activate'
);

/**
 * Plugin activation.
 *
 * @return void
 */
function formflow_activate() {

	if ( ! formflow_is_cf7_active() ) {

		deactivate_plugins( plugin_basename( FORMFLOW_FILE ) );

		wp_die(
			esc_html__(
				'FormFlow requires Contact Form 7 to be installed and activated.',
				'formflow'
			),
			esc_html__( 'Plugin dependency missing', 'formflow' ),
			array(
				'back_link' => true,
			)
		);
	}

	FormFlow_Installer::activate();
}

/**
 * Show admin notice if Contact Form 7 is missing.
 *
 * @return void
 */
function formflow_missing_cf7_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e(
				'FormFlow requires Contact Form 7 to be installed and activated.',
				'formflow'
			); ?>
		</p>
	</div>
	<?php
}

// Stop loading if Contact Form 7 isn't active.
if ( ! formflow_is_cf7_active() ) {
	add_action( 'admin_notices', 'formflow_missing_cf7_notice' );
	return;
}

// Load the plugin.
require_once FORMFLOW_PATH . 'includes/class-plugin.php';

/**
 * Initialize the plugin.
 *
 * @return FormFlow_Plugin
 */
function formflow() {

	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new FormFlow_Plugin();
	}

	return $plugin;
}

formflow();