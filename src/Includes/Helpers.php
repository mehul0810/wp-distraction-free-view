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
	 * Whether automatic button injection is temporarily suspended.
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	private static $suspend_button_injection = false;

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
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$id = $post->ID;
		}

		$id       = absint( $id );
		$btn_text = self::get_button_text();

		if ( ! $id ) {
			return '';
		}

		return sprintf(
			'<div class="wpdfv-fullscreen-container"><button type="button" class="wpdfv-fullscreen-btn" data-post-id="%1$s">%2$s</button></div>',
			esc_attr( $id ),
			esc_html( $btn_text )
		);
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
		$default_text = self::get_default_button_text();
		$settings     = self::get_settings();

		return ! empty( $settings['button_text'] ) ? sanitize_text_field( $settings['button_text'] ) : $default_text;
	}

	/**
	 * Get the value of a settings field
	 *
	 * @param string $option  settings field name.
	 * @param string $section the section name this field belongs to.
	 * @param string $default_value Default text if it's not found.
	 *
	 * @since  1.4.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_option( $option, $section, $default_value = '' ) {
		$section = "wpdfv_{$section}";
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default_value;
	}

	/**
	 * This helper function is used to display read mode button at.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return string
	 */
	public static function display_location() {
		$settings         = self::get_settings();
		$display_location = ! empty( $settings['display_location'] ) ? sanitize_key( $settings['display_location'] ) : 'after_content';
		$allowed_values   = [
			'disable',
			'before_content',
			'after_content',
		];

		return in_array( $display_location, $allowed_values, true ) ? $display_location : 'after_content';
	}

	/**
	 * Get Settings.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( 'wpdfv_settings', [] );

		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Get default button text.
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_default_button_text() {
		return esc_html__( 'Read Mode', 'wp-distraction-free-view' );
	}

	/**
	 * Where to display?
	 *
	 * @since  1.6.0
	 * @access public
	 *
	 * @return array
	 */
	public static function where_to_display() {
		$settings = self::get_settings();

		if ( ! array_key_exists( 'where_to_display', $settings ) ) {
			return [ 'post', 'page' ];
		}

		if ( ! is_array( $settings['where_to_display'] ) ) {
			return [];
		}

		return array_values( array_unique( array_map( 'sanitize_key', $settings['where_to_display'] ) ) );
	}

	/**
	 * Check whether automatic button injection is suspended.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_button_injection_suspended() {
		return self::$suspend_button_injection;
	}

	/**
	 * Run a callback while automatic button injection is suspended.
	 *
	 * @since 2.0.0
	 *
	 * @param callable $callback Callback to run.
	 *
	 * @return mixed
	 */
	public static function without_button_injection( callable $callback ) {
		$previous_state                 = self::$suspend_button_injection;
		self::$suspend_button_injection = true;

		try {
			return $callback();
		} finally {
			self::$suspend_button_injection = $previous_state;
		}
	}
}
