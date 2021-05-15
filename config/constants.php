<?php
// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version in SemVer format.
if ( ! defined( 'WPDFV_VERSION' ) ) {
	define( 'WPDFV_VERSION', '1.6.0' );
}

// Define plugin root File.
if ( ! defined( 'WPDFV_PLUGIN_FILE' ) ) {
	define( 'WPDFV_PLUGIN_FILE', dirname( dirname( __FILE__ ) ) . '/wp-distraction-free-view.php' );
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
