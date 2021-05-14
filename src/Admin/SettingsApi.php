<?php
/**
 * WP Distraction Free View - Admin Settings API
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distraction Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

namespace WPDFV\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheating Huh?' );
}

/**
 * WPDFV_Admin_Settings_API.
 *
 * WP Settings API Class.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'SettingsApi' ) ) :

	/**
	 * WPDFV_Admin_Settings_API Class
	 *
	 * @since 1.0.0
	 */
	class SettingsApi {

		/**
		 * Base Prefix for Settings.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $prefix = '';

		/**
		 * Fields array.
		 *
		 * @var   array
		 * @since 1.0.0
		 */
		public $fields = [];

		/**
		 * Constructor.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_action( 'wp_ajax_wpdfv_save_admin_settings', [ $this, 'save_settings' ] );
		}

		/**
		 * Get Active Tab.
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return string
		 */
		public function get_active_tab() {
			return ! empty( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : '';
		}

		/**
		 * Render Settings Page.
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return void
		 */
		public function settings_page() {
			$active_tab = $this->get_active_tab();
			?>
			<div class="wrap wpdfv-form-field-group-wrap">
				<div class="wpdfv-form-field-group-content">
					<?php
					if ( 'recommended_plugins' === $active_tab ) {
						?>
						<h2 class="wpdfv-form-field-group-content-title">
							<?php esc_html_e( 'Recommended Plugins', 'wpdfv' ); ?>
						</h2>
						<?php
						$this->render_recommended_plugins();
					} else {
						?>
						<h2 class="wpdfv-form-field-group-content-title">
							<?php esc_html_e( 'Settings', 'wpdfv' ); ?>
						</h2>
						<?php
						$this->render_settings_form();
					}
					?>
				</div>
				<div class="wpdfv-form-field-group-sidebar">
				</div>
			</div>
			<?php
		}

		/**
		 * Render Settings Form.
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return void
		 */
		public function render_settings_form() {
			?>
			<form id="wpdfv-admin-settings-form" method="POST" action="options.php">
				<?php
				foreach ( $this->fields as $field ) {
					?>
					<div class="wpdfv-form-field-group">
						<?php echo $this->render_form_field( $field ); ?>
					</div>
					<?php
				}
				?>
				<input
					id="wpdfv-save-settings"
					type="button"
					class="button button-primary"
					value="<?php esc_html_e( 'Save', 'wpdfv' ); ?>"
					data-processing-text="<?php esc_html_e( 'Saving...', 'wpdfv' ); ?>"
					data-default-text="<?php esc_html_e( 'Save', 'wpdfv' ); ?>"
					data-saved-text="<?php esc_html_e( 'Saved!', 'wpdfv' ); ?>"
				/>
			</form>
			<?php
		}

		/**
		 * Render Form Field.
		 *
		 * @param array $args List of field arguments.
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return mixed
		 */
		public function render_form_field( $args ) {
			$html        = '';
			$name        = ! empty( $args['name'] ) ? $args['name'] : '';
			$label       = ! empty( $args['label'] ) ? $args['label'] : '';
			$description = ! empty( $args['description'] ) ? $args['description'] : '';
			$default     = ! empty( $args['default'] ) ? $args['default'] : '';
			$settings    = get_option( "{$this->prefix}_settings" );

			$html .= sprintf(
				'<div class="wpdfv-form-field-group-title">%1$s</div>',
				$label
			);

			switch ( $args['type'] ) {
				case 'text':
					$html .= sprintf(
						'<input type="text" name="%1$s[%2$s]" value="%3$s"/>',
						"{$this->prefix}_settings",
						$name,
						! empty( $settings[ $name ] ) ? $settings[ $name ] : $default
					);
					break;
				case 'radio_inline':
					foreach ( $args['options'] as $key => $value ) {
						$field_value = ! empty( $settings[ $name ] ) ? $settings[ $name ] : $default;
						$checked     = checked( $field_value, $key, false );

						$html .= sprintf(
							'<div class="wpdfv-form-field-group-item"><input type="radio" name="%1$s[%2$s]" value="%3$s" %4$s/>%5$s</div>',
							"{$this->prefix}_settings",
							$name,
							$key,
							$checked,
							$value
						);
					}
					break;
				case 'checkbox_inline':
					foreach ( $args['options'] as $option ) {
						$field_value = ! empty( $settings[ $name ] ) ? $settings[ $name ] : $default;
						$checked     = checked( in_array( $option->name, $field_value, true ), true, false );

						$html .= sprintf(
							'<div class="wpdfv-form-field-group-item"><input type="checkbox" name="%1$s[%2$s][]" value="%3$s" %4$s/>%5$s</div>',
							"{$this->prefix}_settings",
							$name,
							$option->name,
							$checked,
							$option->label
						);
					}
					break;
			}

			$html .= sprintf(
				'<p class="wpdfv-form-field-group-description">%1$s</p>',
				$description
			);

			return $html;
		}

		/**
		 * Render Recommended Plugins.
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return void
		 */
		public function render_recommended_plugins() {
			$plugins = [
				[
					'label'       => esc_html__( 'Perform', 'wpdfv' ),
					'description' => esc_html__( 'This plugin helps you optimize your WordPress site in addition to caching.', 'wpdfv' ),
					'url'         => esc_url( 'https://wordpress.org/plugins/perform' ),
				],
				[
					'label'       => esc_html__( 'Klaive - Integrates Klaviyo + GiveWP', 'wpdfv' ),
					'description' => esc_html__( 'This plugin helps you grow your email list on Klaviyo using GiveWP donations plugin.', 'wpdfv' ),
					'url'         => esc_url( 'https://wordpress.org/plugins/klaive' ),
				],
			];
			?>
			<div class="wpdfv-plugin-card-wrap">
				<?php
				foreach ( $plugins as $plugin ) {
					?>
					<div class="wpdfv-plugin-card">
						<h3><?php print_r( $plugin['label'] ); ?></h3>
						<p>
							<?php print_r( $plugin['description'] ); ?>
						</p>
						<a class="button button-secondary" target="_blank" href="<?php print_r( $plugin['url'] ); ?>">
							<?php esc_html_e( 'Know more', 'wpdfv' ); ?>
						</a>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}

		/**
		 * Save Settings
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return void
		 */
		public function save_settings() {
			$key  = "{$this->prefix}_settings";
			$data = $_POST[ $key ];

			// Save settings to options table.
			$is_updated = update_option( $key, $data );

			// Return response based on the need.
			if ( $is_updated ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}
	}

endif;
