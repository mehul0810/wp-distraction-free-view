<?php
/**
 * WP Distraction Free View | Admin Actions.
 *
 * @package WordPress
 * @subpackage WP Distraction Free View
 * @since 1.0.0
 */

namespace WPDFV\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {
	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
	}

	/**
	 * Register Admin Assets.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_admin_assets() {
		// Add Color Picker support.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style( 'wpdfv-admin', WPDFV_PLUGIN_URL . 'assets/dist/css/wpdfv-admin.css' );
		wp_enqueue_script( 'wpdfv-admin', WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv-admin.js' );
	}
}
