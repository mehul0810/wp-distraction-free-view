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
		$asset_file = include WPDFV_PLUGIN_DIR . 'assets/dist/wpdfv-admin.asset.php';

		// Add Color Picker support.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style( 'wpdfv-admin', WPDFV_PLUGIN_URL . 'assets/dist/wpdfv-admin.css', '', $asset_file['version'] );
		wp_enqueue_script(
			'wpdfv-admin',
			WPDFV_PLUGIN_URL . 'assets/dist/wpdfv-admin.js',
			array_merge( [ 'jquery' ], $asset_file['dependencies'] ),
			$asset_file['version'],
			true
		);

		// Localize script with nonce for AJAX security.
		$wpdfv_admin_args = [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpdfv_admin_nonce' ),
		];
		wp_localize_script( 'wpdfv-admin', 'wpdfvAdmin', $wpdfv_admin_args );
	}
}
