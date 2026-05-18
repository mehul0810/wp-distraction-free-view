<?php
/**
 * WP Distraction Free View | Reader mode settings and helpers.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared Reader Mode defaults, sanitization, and calculations.
 *
 * @since 1.7.0
 */
class Reader {
	/**
	 * Reader Mode query parameter.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	const QUERY_PARAM = 'reader-mode';

	/**
	 * Visitor preference localStorage key.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	const PREFERENCES_STORAGE_KEY = 'wpdfv_reader_preferences';

	/**
	 * Get normalized default settings.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return [
			'automatic_button_enabled'    => false,
			'where_to_display'            => [ 'post', 'page' ],
			'display_location'            => 'manual_only',
			'button_text'                 => self::get_default_button_text(),
			'exit_button_text'            => self::get_default_exit_button_text(),
			'modal_template'              => Templates::DEFAULT_TEMPLATE,
			'reading_progress_enabled'    => true,
			'reading_time_enabled'        => true,
			'preference_controls_enabled' => true,
			'default_reader_theme'        => 'light',
			'default_content_width'       => 'default',
			'default_font_size'           => 'default',
		];
	}

	/**
	 * Get stored settings merged with current defaults.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( 'wpdfv_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return self::sanitize_settings_data( array_merge( self::get_default_settings(), $settings ) );
	}

	/**
	 * Sanitize reader settings.
	 *
	 * @since 1.7.0
	 *
	 * @param array $data              Raw settings.
	 * @param array $public_post_types Optional allowed post type slugs.
	 *
	 * @return array
	 */
	public static function sanitize_settings_data( $data, $public_post_types = [] ) {
		$defaults = self::get_default_settings();

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		if ( empty( $public_post_types ) ) {
			$public_post_types = self::get_public_post_type_slugs();
		}

		$display_location = isset( $data['display_location'] ) ? sanitize_key( $data['display_location'] ) : $defaults['display_location'];

		if ( 'disable' === $display_location ) {
			$display_location = 'manual_only';
		}

		if ( ! in_array( $display_location, self::get_allowed_display_locations(), true ) ) {
			$display_location = $defaults['display_location'];
		}

		$where_to_display = isset( $data['where_to_display'] ) && is_array( $data['where_to_display'] ) ? $data['where_to_display'] : $defaults['where_to_display'];
		$where_to_display = array_values( array_unique( array_map( 'sanitize_key', $where_to_display ) ) );
		$where_to_display = array_values( array_intersect( $where_to_display, array_map( 'sanitize_key', $public_post_types ) ) );

		$button_text      = isset( $data['button_text'] ) ? sanitize_text_field( $data['button_text'] ) : $defaults['button_text'];
		$exit_button_text = isset( $data['exit_button_text'] ) ? sanitize_text_field( $data['exit_button_text'] ) : $defaults['exit_button_text'];
		$modal_template   = isset( $data['modal_template'] ) ? Templates::sanitize_template_slug( $data['modal_template'] ) : $defaults['modal_template'];
		$reader_theme     = isset( $data['default_reader_theme'] ) ? sanitize_key( $data['default_reader_theme'] ) : $defaults['default_reader_theme'];
		$content_width    = isset( $data['default_content_width'] ) ? sanitize_key( $data['default_content_width'] ) : $defaults['default_content_width'];
		$font_size        = isset( $data['default_font_size'] ) ? sanitize_key( $data['default_font_size'] ) : $defaults['default_font_size'];

		$automatic_enabled = isset( $data['automatic_button_enabled'] ) ? (bool) $data['automatic_button_enabled'] : $defaults['automatic_button_enabled'];

		if ( 'manual_only' === $display_location ) {
			$automatic_enabled = false;
		}

		return [
			'automatic_button_enabled'    => $automatic_enabled,
			'where_to_display'            => $where_to_display,
			'display_location'            => $display_location,
			'button_text'                 => '' !== $button_text ? $button_text : $defaults['button_text'],
			'exit_button_text'            => '' !== $exit_button_text ? $exit_button_text : $defaults['exit_button_text'],
			'modal_template'              => $modal_template,
			'reading_progress_enabled'    => isset( $data['reading_progress_enabled'] ) ? (bool) $data['reading_progress_enabled'] : $defaults['reading_progress_enabled'],
			'reading_time_enabled'        => isset( $data['reading_time_enabled'] ) ? (bool) $data['reading_time_enabled'] : $defaults['reading_time_enabled'],
			'preference_controls_enabled' => isset( $data['preference_controls_enabled'] ) ? (bool) $data['preference_controls_enabled'] : $defaults['preference_controls_enabled'],
			'default_reader_theme'        => in_array( $reader_theme, self::get_allowed_reader_themes(), true ) ? $reader_theme : $defaults['default_reader_theme'],
			'default_content_width'       => in_array( $content_width, self::get_allowed_content_widths(), true ) ? $content_width : $defaults['default_content_width'],
			'default_font_size'           => in_array( $font_size, self::get_allowed_font_sizes(), true ) ? $font_size : $defaults['default_font_size'],
		];
	}

	/**
	 * Get default reader toggle text.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public static function get_default_button_text() {
		return esc_html__( 'Read in Reader Mode', 'wp-distraction-free-view' );
	}

	/**
	 * Get default reader exit text.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public static function get_default_exit_button_text() {
		return esc_html__( 'Exit Reader Mode', 'wp-distraction-free-view' );
	}

	/**
	 * Get allowed automatic toggle placements.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_allowed_display_locations() {
		return [ 'manual_only', 'before_content', 'after_content', 'floating' ];
	}

	/**
	 * Get allowed reader themes.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_allowed_reader_themes() {
		return [ 'light', 'dark', 'sepia' ];
	}

	/**
	 * Get allowed reader content widths.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_allowed_content_widths() {
		return [ 'narrow', 'default', 'wide' ];
	}

	/**
	 * Get allowed reader font sizes.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_allowed_font_sizes() {
		return [ 'small', 'default', 'large' ];
	}

	/**
	 * Get display location options for settings UI.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_display_location_options() {
		return [
			[
				'label' => esc_html__( 'Manual only', 'wp-distraction-free-view' ),
				'value' => 'manual_only',
			],
			[
				'label' => esc_html__( 'Before content', 'wp-distraction-free-view' ),
				'value' => 'before_content',
			],
			[
				'label' => esc_html__( 'After content', 'wp-distraction-free-view' ),
				'value' => 'after_content',
			],
			[
				'label' => esc_html__( 'Floating button', 'wp-distraction-free-view' ),
				'value' => 'floating',
			],
		];
	}

	/**
	 * Get reader theme options for settings UI.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_reader_theme_options() {
		return [
			[
				'label' => esc_html__( 'Light', 'wp-distraction-free-view' ),
				'value' => 'light',
			],
			[
				'label' => esc_html__( 'Dark', 'wp-distraction-free-view' ),
				'value' => 'dark',
			],
			[
				'label' => esc_html__( 'Sepia', 'wp-distraction-free-view' ),
				'value' => 'sepia',
			],
		];
	}

	/**
	 * Get content width options for settings UI.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_content_width_options() {
		return [
			[
				'label' => esc_html__( 'Narrow', 'wp-distraction-free-view' ),
				'value' => 'narrow',
			],
			[
				'label' => esc_html__( 'Default', 'wp-distraction-free-view' ),
				'value' => 'default',
			],
			[
				'label' => esc_html__( 'Wide', 'wp-distraction-free-view' ),
				'value' => 'wide',
			],
		];
	}

	/**
	 * Get font size options for settings UI.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_font_size_options() {
		return [
			[
				'label' => esc_html__( 'Small', 'wp-distraction-free-view' ),
				'value' => 'small',
			],
			[
				'label' => esc_html__( 'Default', 'wp-distraction-free-view' ),
				'value' => 'default',
			],
			[
				'label' => esc_html__( 'Large', 'wp-distraction-free-view' ),
				'value' => 'large',
			],
		];
	}

	/**
	 * Determine whether automatic toggle insertion is enabled.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public static function is_automatic_button_enabled() {
		$settings = self::get_settings();

		return ! empty( $settings['automatic_button_enabled'] ) && 'manual_only' !== $settings['display_location'];
	}

	/**
	 * Get enabled post type slugs.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function where_to_display() {
		$settings = self::get_settings();

		return isset( $settings['where_to_display'] ) && is_array( $settings['where_to_display'] ) ? $settings['where_to_display'] : [];
	}

	/**
	 * Get automatic display location.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public static function display_location() {
		$settings = self::get_settings();

		return isset( $settings['display_location'] ) ? $settings['display_location'] : 'manual_only';
	}

	/**
	 * Determine whether a post type is enabled for Reader Mode.
	 *
	 * @since 1.7.0
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return bool
	 */
	public static function is_post_type_enabled( $post_type ) {
		return in_array( sanitize_key( $post_type ), self::where_to_display(), true );
	}

	/**
	 * Determine whether the current request asks to open Reader Mode.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public static function is_reader_mode_request() {
		if ( ! isset( $_GET[ self::QUERY_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only display state.
			return false;
		}

		$value = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_PARAM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only display state.

		return in_array( $value, [ '1', 'true', 'yes' ], true );
	}

	/**
	 * Calculate estimated reading time in minutes.
	 *
	 * @since 1.7.0
	 *
	 * @param string $content Post content.
	 *
	 * @return int
	 */
	public static function calculate_reading_time( $content ) {
		$content = strip_shortcodes( (string) $content );
		$content = function_exists( 'strip_blocks' ) ? strip_blocks( $content ) : preg_replace( '/<!--\s+\/?wp:.*?-->/s', ' ', $content );
		$content = wp_strip_all_tags( html_entity_decode( $content, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
		$words   = str_word_count( $content );

		/**
		 * Filter the words-per-minute value used for Reader Mode estimates.
		 *
		 * @since 1.7.0
		 *
		 * @param int $words_per_minute Words per minute.
		 */
		$words_per_minute = absint( apply_filters( 'wpdfv_reading_time_words_per_minute', 200 ) );
		$words_per_minute = $words_per_minute > 0 ? $words_per_minute : 200;

		return max( 1, (int) ceil( $words / $words_per_minute ) );
	}

	/**
	 * Get the localized reading time label for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string
	 */
	public static function get_reading_time_label( \WP_Post $post ) {
		return self::format_reading_time_label( self::calculate_reading_time( $post->post_content ) );
	}

	/**
	 * Format a localized reading time label.
	 *
	 * @since 1.7.0
	 *
	 * @param int $minutes Reading time in minutes.
	 *
	 * @return string
	 */
	public static function format_reading_time_label( $minutes ) {
		$minutes = max( 1, absint( $minutes ) );

		return sprintf(
			/* translators: %s: Reading time in minutes. */
			_n( '%s min read', '%s min read', $minutes, 'wp-distraction-free-view' ),
			number_format_i18n( $minutes )
		);
	}

	/**
	 * Get public post type slugs.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected static function get_public_post_type_slugs() {
		if ( ! function_exists( 'get_post_types' ) ) {
			return [ 'post', 'page' ];
		}

		return array_keys( get_post_types( [ 'public' => true ], 'objects' ) );
	}
}
