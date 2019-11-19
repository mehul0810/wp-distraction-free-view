<?php
/**
 * WPDFV - Frontend Actions
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distraction Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Frontend Assets (JS and CSS).
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpdfv_enqueue_assets(){

	wp_enqueue_script( 'wpdfv-core', WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv.js', array( 'jquery' ), WPDFV_VERSION, true );

	$wpdfv_args = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	);
	wp_localize_script( 'wpdfv-core', 'wpdfv', $wpdfv_args );

	wp_enqueue_style( 'wpdfv-core',WPDFV_PLUGIN_URL . 'assets/dist/css/wpdfv.css','', WPDFV_VERSION );
}
add_action( 'wp_enqueue_scripts', 'wpdfv_enqueue_assets' );

/**
 * Display Overlay Content.
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function wpdfv_scripts_to_footer(){
	?>
	<div class="wpdfv-fullscreen-overlay-container" style="display:none;">
		<div class="wpdfv-fullscreen-overlay-header">
			<div class="wpdfv-actions">
				<?php if(get_option('wpdfv_settings_enable_print') > 0){ ?>
					<a class="btn btn-primary wpdfv-overlay-print wpdfv-overlay-btn">
						<img class="wpdfv-icon" src="<?php echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/print.svg' ); ?>" alt="<?php echo esc_html__( 'Print', 'wpdfv' ); ?>"/>
					</a>
				<?php } ?>

				<?php if(get_option('wpdfv_settings_enable_fullscreen') > 0){ ?>
					<a class="wpdfv-dual-fullscreen-btn wpdfv-overlay-btn">
						<img class="wpdfv-icon" src="<?php echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/fullscreen.svg' ); ?>" alt="<?php esc_html_e( 'Fullscreen', 'wpdfv' ); ?>" />
					</a>
				<?php } ?>
				<a class="wpdfv-overlay-close wpdfv-overlay-btn">
					<img class="wpdfv-icon" src="<?php echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/close.svg' ); ?>" alt="<?php esc_html_e( 'Close', 'wpdfv' ); ?>" />
				</a>
			</div>
		</div>
		<div class="wpdfv-overlay-wrap" id="print"></div>
	</div>
	<?php
}
add_action( 'wp_footer', 'wpdfv_scripts_to_footer' );
