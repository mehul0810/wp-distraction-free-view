<?php
/**
 * WPDFV - Uninstall
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distraction Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

// Bailout unless WordPress is running this file through the uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes all options the plugin stores on the current site.
 *
 * @since 1.8.3
 *
 * @return void
 */
function wpdfv_uninstall_delete_options() {
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
	delete_option( 'wpdfv_settings' );
	delete_option( 'wpdfv_general' );
	delete_option( 'wpdfv_version' );
	delete_option( 'wpdfv_upgrade_error' );
}

// Plugin supports network activation, so clean up every site on the network.
if ( ! is_multisite() ) {
	wpdfv_uninstall_delete_options();
	return;
}

$number = 100;
$offset = 0;

do {
	$site_ids = get_sites(
		[
			'fields' => 'ids',
			'number' => $number,
			'offset' => $offset,
		]
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		wpdfv_uninstall_delete_options();
		restore_current_blog();
	}

	$offset += $number;
} while ( count( $site_ids ) === $number );
