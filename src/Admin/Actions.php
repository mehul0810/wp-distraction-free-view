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
	public function register_admin_assets( $hook_suffix ) {
		if ( 'settings_page_wpdfv_settings' !== $hook_suffix ) {
			return;
		}

		$asset_path = WPDFV_PLUGIN_DIR . 'assets/dist/js/wpdfv-admin.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : [
			'dependencies' => [ 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ],
			'version'      => WPDFV_VERSION,
		];

		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style(
			'wpdfv-admin',
			WPDFV_PLUGIN_URL . 'assets/dist/wpdfv-admin.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_enqueue_script(
			'wpdfv-admin',
			WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv-admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$code_editor_settings = wp_enqueue_code_editor( [ 'type' => 'text/css' ] );

		wp_add_inline_script(
			'wpdfv-admin',
			'window.wpdfvAdminSettings = ' . wp_json_encode(
				[
					'codeEditor' => $code_editor_settings,
				]
			) . ';',
			'before'
		);

		wp_set_script_translations( 'wpdfv-admin', 'wp-distraction-free-view', WPDFV_PLUGIN_DIR . 'languages' );
	}
}
