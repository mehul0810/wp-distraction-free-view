<?php
/**
 * WPDFV - Frontend Actions
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
 * Enqueue Frontend Assets (JS and CSS).
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpdfv_enqueue_assets(){

	if ( get_option('wpdfv_settings_enable_font_awesome')) {
		wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css','', WPDFV_VERSION);
	}

	wp_enqueue_style('wpdfv-overlay',WPDFV_PLUGIN_URL.'assets/css/overlay.css','', WPDFV_VERSION);
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

/**
 * Generate Dynamic CSS.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpdfv_generate_dynamic_css() {

	// Fetch the dynamic styling values.
	$btn_text_fontsize    = get_option('wpdfv_settings_btn_text_fontsize');
	$btn_icon_fontsize    = get_option('wpdfv_settings_btn_icon_fontsize');
	$btn_bg_color         = get_option('wpdfv_settings_btn_bg_color');
	$btn_text_color       = get_option('wpdfv_settings_btn_text_color');
	$btn_hover_bg_color   = get_option('wpdfv_settings_btn_hover_bg_color');
	$btn_hover_text_color = get_option('wpdfv_settings_btn_hover_text_color');
	$btn_padding          = get_option('wpdfv_settings_btn_padding');

	// Generate Custom CSS.
	$custom_css = "";
	$custom_css .= " .wpdfv-overlay-btn span { font-size: ".$btn_icon_fontsize."px; } ";
	$custom_css .= " .wpdfv-overlay-btn { background-color: ".$btn_bg_color."; color: ".$btn_text_color."; font-size: ".$btn_text_fontsize."px; padding: ".$btn_padding."; } ";
	$custom_css .= " button.wpdfv-overlay-btn:hover, button.wpdfv-overlay-btn:focus, button.wpdfv-overlay-btn:visited, button.wpdfv-overlay-btn:active { background-color: ".$btn_hover_bg_color."; color: ".$btn_hover_text_color."; } ";

	wp_add_inline_style( 'wpdfv-overlay', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'wpdfv_generate_dynamic_css' );
