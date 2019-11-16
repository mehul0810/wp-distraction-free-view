<?php
/**
 * WPDFV - Admin Actions
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distration Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Admin Assets (JS and CSS)
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpdfv_enqueue_assets(){

	// Add Color Picker support.
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	// Load admin settings JS.
	wp_enqueue_script(
		'wpdfv-settings',
		WPDFV_PLUGIN_URL . 'assets/js/settings.js',
		array( 'jquery' ),
		WPDFV_VERSION,
		true
	);
}

add_action( 'admin_enqueue_scripts', 'wpdfv_enqueue_assets' );
