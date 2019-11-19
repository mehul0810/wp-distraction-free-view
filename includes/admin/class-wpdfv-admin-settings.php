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

// Bail out, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//if ( ! class_exists( 'WPDFV_Admin_Settings' ) ) {

	/**
	 * Class WPDFV_Admin_Settings
	 *
	 * @since 1.0.0
	 */
	class WPDFV_Admin_Settings extends WPDFV_Admin_Settings_API {

		/**
		 * Admin Settings API.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public $settings_api = array();

		/**
		 * List of Tabs.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public $tabs = array();

		/**
		 * Main constructor.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function __construct() {

			parent::__construct();

			$this->prefix = 'wpdfv_';

			$this->tabs   = array(
				'general'        => __( 'General', 'wpdfv' ),
			);

			$this->add_tabs();
			$this->add_fields();

			// Admin Menu.
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 9 );

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
				array( $this, 'settings_page' )
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
					array(
						'id'    => $this->prefix . $slug,
						'title' => $name,
					)
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
				array(
					'id'      => 'display_read_mode_at',
					'type'    => 'radio',
					'name'    => __( 'Display Read Mode', 'wpdfv' ),
					'desc'    => __( 'Enabling this will display "Read Mode" link at your preferred location.', 'wpdfv' ),
					'options' => array(
						'disable'        => __( 'Disable', 'wpdfv' ),
						'before_content' => __( 'Before Content', 'wpdfv' ),
						'after_content'  => __( 'After Content', 'wpdfv' ),
					),
					'default' => 'after_content',
				)
			);

			$this->add_field(
				"{$this->prefix}general",
				array(
					'id'      => 'read_mode_btn_text',
					'type'    => 'text',
					'name'    => __( 'Read Mode Button Text', 'wpdfv' ),
					'desc'    => __( 'This setting will help you change the default text of the "Read Mode" button.', 'wpdfv' ),
					'default' => __( 'Read Mode', 'wpdfv' ),
				)
			);

		}
	}

new WPDFV_Admin_Settings();
