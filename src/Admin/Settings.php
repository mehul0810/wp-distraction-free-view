<?php
/**
 * WP Distraction Free View - Admin Settings.
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distraction Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

namespace WPDFV\Admin;

use WPDFV\Admin\SettingsApi as SettingsApi;

// Bail out, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings extends SettingsApi {

	/**
	 * Admin Settings API.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public $settings_api = [];

	/**
	 * List of Tabs.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public $tabs = [];

	/**
	 * Main constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->prefix = 'wpdfv_';

		$this->tabs = [
			'general' => __( 'General', 'wpdfv' ),
		];

		$this->add_tabs();
		$this->add_fields();

		// Admin Menu.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 9 );

	}

	/**
	 * This function will add admin menu.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Distraction Free Mode', 'wpdfv' ),
			__( 'Distraction Free Mode', 'wpdfv' ),
			'manage_options',
			'wp_distraction_free_view',
			[ $this, 'settings_page' ]
		);

	}

	/**
	 * Add Tabs
	 *
	 * @since 1.0.0
	 */
	public function add_tabs() {
		foreach ( $this->tabs as $slug => $name ) {

			$this->add_section(
				[
					'id'    => $this->prefix . $slug,
					'title' => $name,
				]
			);

		}

	}

	/**
	 * This function will add fields.
	 *
	 * @since 1.0.0
	 */
	public function add_fields() {
		$this->add_field(
			"{$this->prefix}general",
			[
				'id'      => 'display_read_mode_at',
				'type'    => 'radio',
				'name'    => __( 'Display Read Mode', 'wpdfv' ),
				'desc'    => __( 'Enabling this will display "Read Mode" link at your preferred location.', 'wpdfv' ),
				'options' => [
					'disable'        => __( 'Disable', 'wpdfv' ),
					'before_content' => __( 'Before Content', 'wpdfv' ),
					'after_content'  => __( 'After Content', 'wpdfv' ),
				],
				'default' => 'after_content',
			]
		);

		$this->add_field(
			"{$this->prefix}general",
			[
				'id'      => 'read_mode_btn_text',
				'type'    => 'text',
				'name'    => __( 'Read Mode Button Text', 'wpdfv' ),
				'desc'    => __( 'This setting will help you change the default text of the "Read Mode" button.', 'wpdfv' ),
				'default' => __( 'Read Mode', 'wpdfv' ),
			]
		);
	}
}
