<?php
/**
 * WP Distraction Free View | Frontend Helpers.
 *
 * @package WordPress
 * @subpackage WP Distraction Free View
 * @since 1.0.0
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {
	/**
	 * This helper function is used to display read mode button.
	 *
	 * @param int $id Post ID.
	 *
	 * @since  1.4.2
	 * @access public
	 *
	 * @return mixed
	 */
	public static function display_read_mode_button( $id = 0 ) {
		// If `$id` is `0`, then get it from `$post` global variable.
		if ( 0 === $id ) {
			global $post;
			$id = $post->ID;
		}

		$html     = '';
		$btn_text = wpdfv_get_button_text();

		$html .= '<div class="wpdfv-fullscreen-container">';
		$html .= sprintf(
			'<a class="wpdfv-fullscreen-btn" data-post-id="%1$s">%2$s</a>',
			$id,
			$btn_text
		);
		$html .= '</div>';

		return $html;
	}

	/**
	 * This function is used to get default button text.
	 *
	 * @since  1.4.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_button_text() {
		$default_text = __( 'Read Mode', 'wpdfv' );

		return wpdfv_get_option( 'read_mode_btn_text', 'general', $default_text );
	}

	/**
	 * Get the value of a settings field
	 *
	 * @param string $option  settings field name.
	 * @param string $section the section name this field belongs to.
	 * @param string $default default text if it's not found.
	 *
	 * @since  1.4.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_option( $option, $section, $default = '' ) {
		$section = "wpdfv_{$section}";
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;

	}

	/**
	 * This helper function is used to display read mode button at.
	 *
	 * @since  1.4.2
	 * @access public
	 *
	 * @return string
	 */
	public static function wpdfv_display_read_mode_btn_at() {
		return wpdfv_get_option( 'display_read_mode_at', 'general', 'after_content' );
	}
}
