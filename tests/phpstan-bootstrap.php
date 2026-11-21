<?php
/**
 * PHPStan bootstrap.
 *
 * Declares the runtime constants that config/constants.php defines
 * conditionally, so static analysis can resolve them. Keep in sync with
 * config/constants.php.
 *
 * @package WPDFV
 */

define( 'WPDFV_VERSION', '0.0.0' );
define( 'WPDFV_TEXT_DOMAIN', 'wp-distraction-free-view' );
define( 'WPDFV_REST_NAMESPACE', 'wp-distraction-free-view/v1' );
define( 'WPDFV_PLUGIN_FILE', dirname( __DIR__ ) . '/wp-distraction-free-view.php' );
define( 'WPDFV_PLUGIN_BASENAME', 'wp-distraction-free-view/wp-distraction-free-view.php' );
define( 'WPDFV_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WPDFV_PLUGIN_URL', 'https://example.com/wp-content/plugins/wp-distraction-free-view/' );
