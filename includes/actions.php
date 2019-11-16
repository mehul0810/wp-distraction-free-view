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
	<script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery('.wpdfv-fullscreen-container a.wpdfv-fullscreen-btn').click(function(e){

                var post_id = jQuery(this).data('post-id');
                var data = {
                    'action': 'display_post_details',
                    'id': post_id
                };

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post('<?php echo admin_url(); ?>admin-ajax.php', data, function(response) {
                    jQuery('.wpdfv-overlay-wrap').css('overflow-y','scroll');
                    jQuery('body').css('overflow-y','hidden');
                    jQuery('.wpdfv-fullscreen-overlay-container .wpdfv-overlay-wrap').html(response);
                    jQuery('.wpdfv-fullscreen-overlay-container').fadeIn('slow');
                });
                e.preventDefault();
            });

            jQuery('.wpdfv-overlay-close').click(function(e){
                jQuery('.wpdfv-overlay-wrap').css('overflow-y','scroll');
                jQuery('body').css('overflow-y','scroll');
                jQuery('.wpdfv-fullscreen-overlay-container').fadeOut('slow');
                e.preventDefault();
            });
        });

        function toggleFullScreen() {
            if (!document.fullscreenElement &&    // alternative standard method
                !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement ) {  // current working methods
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
                jQuery('.wpdfv-overlay-close').hide();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
                jQuery('.wpdfv-overlay-close').show();
            }
        }

        function printDiv(divID) {
            //Get the HTML of div
            var divElements = document.getElementById(divID).innerHTML;
            //Get the HTML of whole page
            var oldPage = document.body.innerHTML;

            //Reset the page's HTML with div's HTML only
            document.body.innerHTML =
                "<html><head><title></title></head><body>" +
                divElements + "</body>";

            //Print Page
            window.print();

            //Restore orignal HTML
            document.body.innerHTML = oldPage;


        }
	</script>

	<div class="wpdfv-fullscreen-overlay-container" style="display:none;">
		<div class="wpdfv-fullscreen-overlay-header">
			<div class="pull-right text-right">
				<?php if(get_option('wpdfv_settings_enable_fullscreen') > 0){ ?>
					<button onclick="javascript:toggleFullScreen(); " class="btn btn-primary wpdfv-overlay-btn"><i class="fa fa-arrows-alt"></i></button>
				<?php } ?>
				<button class="btn btn-primary wpdfv-overlay-close wpdfv-overlay-btn"><i class="fa fa-times"></i></button>
			</div>
			<div class="pull-left">
				<?php if(get_option('wpdfv_settings_enable_print') > 0){ ?>
					<button onclick="javascript:printDiv('print');" class="btn btn-primary pull-left wpdfv-overlay-print wpdfv-overlay-btn"><i class="fa fa-print"></i>Print</button>
				<?php } ?>
			</div>
		</div>
		<div class="wpdfv-overlay-wrap" id="print">

		</div>
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
