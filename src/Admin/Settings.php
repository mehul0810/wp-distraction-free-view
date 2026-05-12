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

use WPDFV\Admin\SettingsApi;
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

		$this->prefix = 'wpdfv';

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
			esc_html__( 'WP Distraction Free View', 'wp-distraction-free-view' ),
			esc_html__( 'Distraction Free Mode', 'wp-distraction-free-view' ),
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
		if ( ! $screen || 'settings_page_wpdfv_settings' !== $screen->id ) {
			return;
		}
		?>
		<div class="wpdfv-dashboard-header">
			<div class="wpdfv-dashboard-header-title">
				<h1>
					<?php echo esc_html( get_admin_page_title() ); ?>
				</h1>
			</div>
		</div>
		<?php
	}
}
