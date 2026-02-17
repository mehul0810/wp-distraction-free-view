<?php
/**
 * WP Distraction Free View | Frontend Actions.
 *
 * @package WordPress
 * @subpackage WP Distraction Free View
 * @since 1.0.0
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
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_footer', [ $this, 'add_overlay_to_footer' ] );
	}

	/**
	 * Register Assets.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_assets() {
		$asset_file = include WPDFV_PLUGIN_DIR . 'assets/dist/wpdfv.asset.php';

		wp_enqueue_script(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/wpdfv.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		$wpdfv_args = [
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'wpdfv_nonce' ),
			'pluginUrl' => WPDFV_PLUGIN_URL,
		];
		wp_localize_script( 'wpdfv-core', 'wpdfv', $wpdfv_args );

		wp_enqueue_style( 'wpdfv-core', WPDFV_PLUGIN_URL . 'assets/dist/wpdfv.css', '', $asset_file['version'] );
	}

	/**
	 * Add Overlay to Footer.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function add_overlay_to_footer() {
		// React component renders its own overlay, we just need the root element
		?>
		<div id="wpdfv-react-root"></div>
		<?php
	}
}
