<?php
// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version in SemVer format.
if ( ! defined( 'WPDFV_VERSION' ) ) {
	define( 'WPDFV_VERSION', '2.2.0' );
}

// Define plugin text domain.
if ( ! defined( 'WPDFV_TEXT_DOMAIN' ) ) {
	define( 'WPDFV_TEXT_DOMAIN', 'wp-distraction-free-view' );
}

// Define plugin REST namespace.
if ( ! defined( 'WPDFV_REST_NAMESPACE' ) ) {
	define( 'WPDFV_REST_NAMESPACE', 'wp-distraction-free-view/v1' );
}

// Define plugin root File.
if ( ! defined( 'WPDFV_PLUGIN_FILE' ) ) {
	define( 'WPDFV_PLUGIN_FILE', dirname( __DIR__, 1 ) . '/wp-distraction-free-view.php' );
}

// Define plugin basename.
if ( ! defined( 'WPDFV_PLUGIN_BASENAME' ) ) {
	define( 'WPDFV_PLUGIN_BASENAME', plugin_basename( WPDFV_PLUGIN_FILE ) );
}

// Define plugin directory Path.
if ( ! defined( 'WPDFV_PLUGIN_DIR' ) ) {
	define( 'WPDFV_PLUGIN_DIR', plugin_dir_path( WPDFV_PLUGIN_FILE ) );
}

// Define plugin directory URL.
if ( ! defined( 'WPDFV_PLUGIN_URL' ) ) {
	define( 'WPDFV_PLUGIN_URL', plugin_dir_url( WPDFV_PLUGIN_FILE ) );
}
