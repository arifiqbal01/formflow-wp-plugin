<?php
/** Copy to a disposable WordPress checkout ONLY; never use on a real website. */
define( 'DB_NAME', getenv( 'FORMFLOW_TEST_DB_NAME' ) ?: 'formflow_test' );
define( 'DB_USER', getenv( 'FORMFLOW_TEST_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'FORMFLOW_TEST_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'FORMFLOW_TEST_DB_HOST' ) ?: '127.0.0.1:33077' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'WP_DEBUG', true );
define( 'WP_HOME', 'http://example.test' );
define( 'WP_SITEURL', 'http://example.test' );
define( 'DISABLE_WP_CRON', true );
$table_prefix = 'wp_';
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
