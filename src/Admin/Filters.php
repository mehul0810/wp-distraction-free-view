<?php
/**
 * WP Distraction Free View | Admin Filters.
 *
 * @package WordPress
 * @subpackage WP Distraction Free View
 * @since 1.0.0
 */

namespace WPDFV\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . WPDFV_PLUGIN_BASENAME, [ $this, 'add_plugin_action_links' ] );
	}

	/**
	 * Plugin page action links.
	 *
	 * @param array $actions An array of plugin action links.
	 *
	 * @since 1.0.1
	 *
	 * @return array
	 */
	public function add_plugin_action_links( $actions ) {
		$new_actions = [
			'settings' => sprintf(
				'<a href="%1$s">%2$s</a>',
				admin_url( 'options-general.php?page=wpdfv_settings' ),
				esc_html__( 'Settings', 'wpdfv' )
			),
			'support'  => sprintf(
				'<a target="_blank" href="%1$s">%2$s</a>',
				esc_url_raw( 'https://wordpress.org/support/plugin/wp-distraction-free-view/' ),
				esc_html__( 'Support', 'wpdfv' )
			),
		];

		return array_merge( $new_actions, $actions );
	}
}
