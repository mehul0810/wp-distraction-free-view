<?php
/**
 * WP Distraction Free View | Frontend Actions.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	/**
	 * Register frontend assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets() {
		if ( ! $this->should_enqueue_assets() ) {
			return;
		}

		$asset_path = WPDFV_PLUGIN_DIR . 'assets/dist/js/wpdfv.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : [
			'dependencies' => [ 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ],
			'version'      => WPDFV_VERSION,
		];

		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/wpdfv.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_enqueue_script(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'wpdfv-core', 'wp-distraction-free-view', WPDFV_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Determine whether frontend assets are needed on the current page.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function should_enqueue_assets() {
		$should_enqueue = false;
		$post           = get_post();

		if ( $post instanceof \WP_Post ) {
			$where_to_display = Helpers::where_to_display();
			$display_location = Helpers::display_location();
			$has_shortcode    = has_shortcode( $post->post_content, 'wpdfv' );

			$should_enqueue = $has_shortcode || ( 'disable' !== $display_location && in_array( $post->post_type, $where_to_display, true ) );
		}

		/**
		 * Filter whether WP Distraction Free View frontend assets should load.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $should_enqueue Whether assets should load.
		 */
		return (bool) apply_filters( 'wpdfv_should_enqueue_frontend_assets', $should_enqueue );
	}
}
