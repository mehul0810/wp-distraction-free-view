<?php
/**
 * @package WP Distraction Free View
 */
/*
Plugin Name: WP Distraction Free View
Plugin URI: https://wordpress.org/plugins/wp-distraction-free-view/
Description: "WP Distraction Free View" plugin provides distraction free viewing mode to the users of the website/blog. 
Version: 1.4
Author: Mehul Gohil
Author URI: http://mehulgohil.in/
License: GPLv2 or later
Text Domain: wpdfv

WP Distraction Free View is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WP Distraction Free View is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP Distraction Free View. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'WPDFV_VERSION', '1.4' );
define( 'WPDFV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPDFV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_TITLE', 'Distraction Free View' );

# Issue Fixed | Generates unwanted characters issue
register_activation_hook( __FILE__, array( 'WPDFV', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPDFV', 'deactivate' ) );

class WPDFV {
    
    static function activate(){
        update_option( 'wpdfv_settings_readmore_button_text', 'Read more' );
        update_option( 'wpdfv_settings_enable_print','0' );
        update_option( 'wpdfv_settings_enable_font_awesome','1' );
        update_option( 'wpdfv_settings_enable_fullscreen','0' );
        update_option( 'wpdfv_settings_btn_bg_color','#000000' );
        update_option( 'wpdfv_settings_btn_text_color','#FFFFFF' );
        update_option( 'wpdfv_settings_btn_hover_bg_color','#E5E5E5' );
        update_option( 'wpdfv_settings_btn_hover_text_color','#FFFFFF' );
        update_option( 'wpdfv_settings_btn_text_fontsize','20' );
        update_option( 'wpdfv_settings_btn_icon_fontsize','26' );
        update_option( 'wpdfv_settings_btn_padding','10px 20px' );
    }
    
    static function deactivate(){
        delete_option( 'wpdfv_settings_readmore_button_text' );
        delete_option( 'wpdfv_settings_enable_print' );
        delete_option( 'wpdfv_settings_enable_font_awesome' );
        delete_option( 'wpdfv_settings_enable_fullscreen' );
        delete_option( 'wpdfv_settings_btn_bg_color' );
        delete_option( 'wpdfv_settings_btn_text_color' );
        delete_option( 'wpdfv_settings_btn_hover_bg_color' );
        delete_option( 'wpdfv_settings_btn_hover_text_color' );
        delete_option( 'wpdfv_settings_btn_text_fontsize' );
        delete_option( 'wpdfv_settings_btn_icon_fontsize' );
        delete_option( 'wpdfv_settings_btn_padding' );
    }
    
}

if ( is_admin() ) {
	require_once( WPDFV_PLUGIN_DIR . 'admin/class.settings.php' );
}

require_once( WPDFV_PLUGIN_DIR . 'class.shortcodes.php' );

# Actions
add_action('wp_enqueue_scripts','wpdfv_enqueue_scripts');
add_action('wp_footer','wpdfv_scripts_to_footer');
add_action( 'wp_enqueue_scripts', 'wpdfv_dynamic_css' );
add_action('admin_enqueue_scripts','wpdfv_add_scripts_to_admin');

/*
 *	@since 1.0
 *	@updated 1.3
 *	@usage Add Styles and Scripts to Admin Settings API
 */
function wpdfv_enqueue_scripts(){
    
    if(get_option('wpdfv_settings_enable_font_awesome')) {
        wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css','', WPDFV_VERSION);    
    }
    
    wp_enqueue_style('overlay',WPDFV_PLUGIN_URL.'assets/css/overlay.css','', WPDFV_VERSION);    
}




/*
 *	@since 1.0
 *	@updated 1.3
 *	@usage Updates Overlay Content using AJAX Call Data
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

/*
 *	@since 1.3
 *	@usage Add Styles and Scripts to Admin Settings API
 */
function wpdfv_add_scripts_to_admin(){
	
	# Added WP Color Picker Support
	wp_enqueue_style( 'wp-color-picker' ); 
	wp_enqueue_script( 'wp-color-picker' ); 
	
	# Added Custom Settings JS for Options Page
	wp_enqueue_script('settings',plugins_url('wp-distraction-free-view').'/assets/js/settings.js');
}

/*
 *	@since 1.3
 *	@usage Generates Dynamic CSS
 */	
function wpdfv_dynamic_css() {
	
	wp_enqueue_style( 'dynamic-style', plugins_url('wp-distraction-free-view') . '/assets/css/dynamic.css' );
	
	# Fetch Already Defined Variables
	$btn_text_fontsize = get_option('wpdfv_settings_btn_text_fontsize');
	$btn_icon_fontsize = get_option('wpdfv_settings_btn_icon_fontsize');
	$btn_bg_color = get_option('wpdfv_settings_btn_bg_color');
	$btn_text_color = get_option('wpdfv_settings_btn_text_color');
	$btn_hover_bg_color = get_option('wpdfv_settings_btn_hover_bg_color');
	$btn_hover_text_color = get_option('wpdfv_settings_btn_hover_text_color');
	$btn_padding = get_option('wpdfv_settings_btn_padding');
	
	# Generate Custom CSS Dynamically
	$custom_css = ""; 
	$custom_css .= " .wpdfv-overlay-btn span { font-size: ".$btn_icon_fontsize."px; } ";
	$custom_css .= " .wpdfv-overlay-btn { background-color: ".$btn_bg_color."; color: ".$btn_text_color."; font-size: ".$btn_text_fontsize."px; padding: ".$btn_padding."; } ";
	$custom_css .= " button.wpdfv-overlay-btn:hover, button.wpdfv-overlay-btn:focus, button.wpdfv-overlay-btn:visited, button.wpdfv-overlay-btn:active { background-color: ".$btn_hover_bg_color."; color: ".$btn_hover_text_color."; } ";

	# Add Dynamic CSS using WP Inline Style Function
	wp_add_inline_style( 'dynamic-style', $custom_css );
}


?>