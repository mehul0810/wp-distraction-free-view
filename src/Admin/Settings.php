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
	 * Main constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$args = [
			'public'   => true,
			'_builtin' => true,
		];

		$this->prefix = 'wpdfv';
		$this->fields = [
			[
				'type'    => 'radio_inline',
				'label'   => esc_html__( 'Where to display?', 'wpdfv' ),
				'name'    => 'where_to_display',
				'options' => get_post_types( $args, 'names', 'and' ),
				'default' => 'after_content',
			],
			[
				'type'    => 'radio_inline',
				'label'   => esc_html__( 'Display Location', 'wpdfv' ),
				'name'    => 'display_location',
				'options' => [
					'before_content' => esc_html__( 'Before Content', 'wpdfv' ),
					'after_content'  => esc_html__( 'After Content', 'wpdfv' ),
				],
				'default' => 'after_content',
			],
			[
				'type'    => 'text',
				'label'   => esc_html__( 'Read More Button Text', 'wpdfv' ),
				'name'    => 'read_more_btn_text',
				'default' => esc_html__( 'Read More', 'wpdfv' ),
			],
		];

		// Admin Menu.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 9 );
		add_action( 'in_admin_header', [ $this, 'render_settings_page_header' ] );
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
			esc_html__( 'WP Distraction Free View', 'wpdfv' ),
			esc_html__( 'Distraction Free Mode', 'wpdfv' ),
			'manage_options',
			'wpdfv_settings',
			[ $this, 'settings_page' ]
		);
	}

	/**
	 * Render Settings Page Header.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function render_settings_page_header() {
		$screen = get_current_screen();

		// Bailout, if screen id doesn't match.
		if ( 'settings_page_wpdfv_settings' !== $screen->id ) {
			return;
		}
		?>
		<div class="wpdfv-dashboard-header">
			<div class="wpdfv-dashboard-header-title">
				<h1>
					<?php echo esc_html( get_admin_page_title() ); ?>
				</h1>
			</div>
			<?php $this->render_header_navigation(); ?>
		</div>
		<?php
	}

	/**
	 * Render Header Navigation.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function render_header_navigation() {
		$screen      = get_current_screen();
		$current_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';
		$tabs        = apply_filters(
			'wpdfv_settings_navigation_tabs',
			[
				'settings'      => [
					'name'  => esc_html__( 'Settings', 'wpdfv' ),
					'url'   => admin_url( 'admin.php?page=wpdfv_settings' ),
					'class' => 'settings_page_wpdfv_settings' === $screen->id && '' === $current_tab ? 'active' : '',
				],
				'other-plugins' => [
					'name'  => esc_html__( 'Recommended Plugins', 'wpdfv' ),
					'url'   => admin_url( 'admin.php?page=wpdfv_settings&tab=recommended_plugins' ),
					'class' => 'settings_page_wpdfv_settings' === $screen->id && 'recommended_plugins' === $current_tab ? 'active' : '',
				],
			],
		);

		// Don't print any markup if we only have one tab.
		if ( count( $tabs ) === 1 ) {
			return;
		}
		?>
		<div class="wpdfv-header-navigation">
			<?php
			foreach ( $tabs as $tab ) {
				printf(
					'<a href="%1$s" class="%2$s">%3$s</a>',
					esc_url( $tab['url'] ),
					esc_attr( $tab['class'] ),
					esc_html( $tab['name'] )
				);
			}
			?>
		</div>
		<?php
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
