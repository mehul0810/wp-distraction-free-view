<?php
/**
 * WP Distraction Free View | Upgrades.
 *
 * @since 1.6.0
 * @author Mehul Gohil <hello@mehulgohil.com>
 */

namespace WPDFV\Admin;

use WPDFV\Includes\Helpers as Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheating huh?' );
}

class Upgrades {
	/**
	 * Constructor.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'process_automatic_upgrades' ], 0 );
	}

	/**
	 * Perform automatic database upgrades when necessary.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function process_automatic_upgrades() {
		$did_upgrade = false;
		$version     = preg_replace( '/[^0-9.].*/', '', get_option( 'wpdfv_version' ) );

		if ( ! $version ) {
			// 1.0.0 is the first version to use this option so we must add it.
			$version = '1.0.0';
		}

		switch ( true ) {
			case version_compare( $version, '1.6.0', '<' ):
				$this->v160_upgrades();
				$did_upgrade = true;
		}

		if ( $did_upgrade || version_compare( $version, WPDFV_VERSION, '<' ) ) {
			update_option( 'wpdfv_version', WPDFV_VERSION, false );
		}
	}

	/**
	 * Upgrade for version 1.6.0
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function v160_upgrades() {
		$default_text       = Helpers::get_default_button_text();
		$display_location   = Helpers::get_option( 'display_read_mode_at', 'general', 'after_content' );
		$read_mode_btn_text = Helpers::get_option( 'read_mode_btn_text', 'general', $default_text );

		// Get admin settings.
		$settings = Helpers::get_settings();

		// Update essential values.
		$settings['display_location'] = $display_location;
		$settings['button_text']      = $read_mode_btn_text;

		// Update admin settings.
		update_option( 'wpdfv_settings', $settings );
	}
}
