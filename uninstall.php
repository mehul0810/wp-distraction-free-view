<?php
/**
 * WPDFV - Uninstall
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