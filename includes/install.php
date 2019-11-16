<?php
/**
 * WPDFV - Install
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

function wpdfv_install() {

}

function wpdfv_add_default_settings() {
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