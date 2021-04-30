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
	exit;
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
		public function __construct() {}

		/**
		 * Get Active Tab.
		 *
		 * @since  1.6.0
		 * @access public
		 *
		 * @return string
		 */
		public function get_active_tab() {
			return ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';
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
			<div class="wrap">
				<?php
				if ( 'recommended_plugins' === $active_tab ) {

				} else {
					$this->render_settings_form();
				}
				?>
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
			<form method="POST" action="options.php">
				<?php
				foreach ( $this->fields as $field ) {
					?>
					<div class="wpdfv-form-field-group">
						<?php echo $this->render_form_field( $field ); ?>
					</div>
					<?php
				}
				?>
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
			$html = '';
			$name = $args['name'];

			switch ( $args['type'] ) {
				case 'text':
					$html .= sprintf(
						'<input type="text" name="%1$s[%2$s]"/>',
						"{$this->prefix}_settings",
						"{$name}"
					);
					break;
				case 'radio_inline':
					foreach ( $args['options'] as $key => $value ) {
						$html .= sprintf(
							'<input type="radio" name="%1$s[%2$s]" value="%3$s"/> %4$s',
							"{$this->prefix}_settings",
							$name,
							$key,
							$value
						);
					}
					break;
			}

			return $html;
		}
	}

endif;
