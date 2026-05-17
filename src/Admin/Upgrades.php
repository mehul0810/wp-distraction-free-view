<?php
/**
 * WP Distraction Free View | Upgrades.
 *
 * @since 1.6.0
 * @author Mehul Gohil <hello@mehulgohil.com>
 */

namespace WPDFV\Admin;

use WPDFV\Includes\Helpers;
use WPDFV\Includes\Reader;
use WPDFV\Includes\Templates;

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
		add_action( 'init', [ $this, 'process_automatic_upgrades' ], 0 );
		add_action( 'admin_notices', [ $this, 'render_upgrade_notice' ] );
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

		if ( ! $version && false === get_option( 'wpdfv_settings', false ) && false === get_option( 'wpdfv_general', false ) ) {
			update_option( 'wpdfv_version', WPDFV_VERSION, false );
			return;
		}

		if ( ! $version ) {
			$version = '1.0.0';
		}

		try {
			switch ( true ) {
				case version_compare( $version, '1.6.0', '<' ):
					$this->v160_upgrades();
					$did_upgrade = true;
					// Fall through so older installs also receive current settings.
				case version_compare( $version, '2.1.0', '<' ):
					$this->v210_upgrades();
					$did_upgrade = true;
					// Fall through so installs receive current Reader Mode defaults.
				case version_compare( $version, '2.2.0', '<' ):
					$this->v220_upgrades();
					$did_upgrade = true;
			}
		} catch ( \Throwable $error ) {
			update_option( 'wpdfv_upgrade_error', sanitize_text_field( $error->getMessage() ), false );
			return;
		}

		if ( $did_upgrade || version_compare( $version, WPDFV_VERSION, '<' ) ) {
			delete_option( 'wpdfv_upgrade_error' );
			update_option( 'wpdfv_version', WPDFV_VERSION, false );
		}
	}

	/**
	 * Render an admin notice if a safe automatic upgrade could not complete.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function render_upgrade_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$error = get_option( 'wpdfv_upgrade_error', '' );

		if ( ! $error ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'WP Distraction Free View could not complete its Reader Mode settings upgrade. Existing settings were left unchanged.', 'wp-distraction-free-view' ); ?>
			</p>
		</div>
		<?php
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

		$settings = get_option( 'wpdfv_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		// Update essential values.
		$settings['display_location'] = $display_location;
		$settings['button_text']      = $read_mode_btn_text;

		// Update admin settings.
		update_option( 'wpdfv_settings', $settings, false );
	}

	/**
	 * Upgrade settings for version 2.1.0.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function v210_upgrades() {
		$settings = get_option( 'wpdfv_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$display_location = isset( $settings['display_location'] ) ? sanitize_key( $settings['display_location'] ) : 'after_content';

		if ( ! array_key_exists( 'automatic_button_enabled', $settings ) ) {
			$settings['automatic_button_enabled'] = 'disable' !== $display_location;
		}

		if ( empty( $settings['modal_template'] ) ) {
			$settings['modal_template'] = Templates::DEFAULT_TEMPLATE;
		} else {
			$settings['modal_template'] = Templates::sanitize_template_slug( $settings['modal_template'] );
		}

		if ( 'disable' === $display_location ) {
			$settings['display_location'] = 'after_content';
		}

		update_option( 'wpdfv_settings', $settings, false );
	}

	/**
	 * Upgrade settings for version 2.2.0.
	 *
	 * This is intentionally a lightweight option migration. It fills new Reader
	 * Mode defaults, normalizes legacy placement values, and leaves existing
	 * labels/post type choices untouched so rollback remains safe.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function v220_upgrades() {
		$settings = get_option( 'wpdfv_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		if ( isset( $settings['display_location'] ) && 'disable' === $settings['display_location'] ) {
			$settings['display_location']         = 'manual_only';
			$settings['automatic_button_enabled'] = false;
		}

		$allowed_post_types = isset( $settings['where_to_display'] ) && is_array( $settings['where_to_display'] ) ? array_map( 'sanitize_key', $settings['where_to_display'] ) : [];
		$allowed_post_types = array_values( array_unique( array_merge( [ 'post', 'page' ], $allowed_post_types ) ) );
		$settings           = array_merge( Reader::get_default_settings(), $settings );
		$settings           = Reader::sanitize_settings_data( $settings, $allowed_post_types );

		update_option( 'wpdfv_settings', $settings, false );
	}
}
