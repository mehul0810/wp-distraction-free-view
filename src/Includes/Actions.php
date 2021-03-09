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
		wp_enqueue_script( 'wpdfv-core', WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv.js', [ 'jquery' ], WPDFV_VERSION, true );

		$wpdfv_args = [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		];
		wp_localize_script( 'wpdfv-core', 'wpdfv', $wpdfv_args );

		wp_enqueue_style( 'wpdfv-core', WPDFV_PLUGIN_URL . 'assets/dist/css/wpdfv.css', '', WPDFV_VERSION );
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
		?>
		<div class="wpdfv-fullscreen-overlay-container" style="display:none;">
			<div class="wpdfv-fullscreen-overlay-header">
				<div class="wpdfv-actions">
					<a class="btn btn-primary wpdfv-overlay-print wpdfv-overlay-btn">
						<img class="wpdfv-icon" src="<?php echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/print.svg' ); ?>" alt="<?php echo esc_html__( 'Print', 'wpdfv' ); ?>"/>
					</a>
					<a class="wpdfv-dual-fullscreen-btn wpdfv-overlay-btn">
						<img class="wpdfv-icon" src="<?php echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/fullscreen.svg' ); ?>" alt="<?php esc_html_e( 'Fullscreen', 'wpdfv' ); ?>" />
					</a>
					<a class="wpdfv-overlay-close wpdfv-overlay-btn">
						<img class="wpdfv-icon" src="<?php echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/close.svg' ); ?>" alt="<?php esc_html_e( 'Close', 'wpdfv' ); ?>" />
					</a>
				</div>
			</div>
			<div class="wpdfv-overlay-wrap" id="wpdfv-print"></div>
		</div>
		<?php
	}
}
