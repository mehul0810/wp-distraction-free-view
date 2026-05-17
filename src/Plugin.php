<?php
namespace WPDFV;

use WPDFV\Admin;
use WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads and registers plugin functionality through WordPress hooks.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Registers functionality with WordPress hooks.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register() {
		// Handle plugin activation and deactivation.
		register_activation_hook( WPDFV_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( WPDFV_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Register services used throughout the plugin.
		add_action( 'plugins_loaded', [ $this, 'register_services' ] );

		// Load text domain.
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
	}

	/**
	 * Registers the individual services of the plugin.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_services() {
		// Load Admin Files.
		new Admin\Actions();
		new Admin\Filters();
		new Admin\Settings();
		new Admin\Upgrades();

		// Load Frontend Files.
		new Includes\Actions();
		new Includes\Blocks();
		new Includes\Filters();
		new Includes\Templates();
		new Includes\Shortcodes\Main();
	}

	/**
	 * Loads the plugin's translated strings.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'wp-distraction-free-view',
			false,
			dirname( plugin_basename( WPDFV_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Handles activation procedures during installation and updates.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param bool $network_wide Optional. Whether the plugin is being enabled on
	 *                           all network sites or a single site. Default false.
	 *
	 * @return void
	 */
	public function activate( $network_wide = false ) {
		if ( false === get_option( 'wpdfv_settings', false ) ) {
			update_option( 'wpdfv_settings', Includes\Reader::get_default_settings(), false );
		}

		update_option( 'wpdfv_version', WPDFV_VERSION, false );
	}

	/**
	 * Handles deactivation procedures.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function deactivate() {}
}
