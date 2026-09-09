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
	 * Normalized settings cache for the current request.
	 *
	 * @since 1.8.0
	 *
	 * @var array|null
	 */
	protected static $settings_cache = null;

	/**
	 * Public post type slugs cache for the current request.
	 *
	 * @since 1.8.0
	 *
	 * @var array|null
	 */
	protected static $public_post_type_slugs_cache = null;

	/**
	 * Maximum custom CSS length in bytes.
	 *
	 * @since 1.8.0
	 *
	 * @var int
	 */
	const CUSTOM_CSS_MAX_LENGTH = 20480;

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
	 * Reader Mode reading position localStorage key.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	const POSITIONS_STORAGE_KEY = 'wpdfv_reader_positions';

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
			'reader_toc_enabled'          => false,
			'reader_resume_enabled'       => false,
			'preference_controls_enabled' => true,
			'default_reader_theme'        => 'light',
			'default_content_width'       => 'default',
			'default_font_size'           => 'default',
			'custom_css'                  => '',
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
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$settings = get_option( 'wpdfv_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		self::$settings_cache = self::sanitize_settings_data( array_merge( self::get_default_settings(), $settings ) );

		return self::$settings_cache;
	}

	/**
	 * Clear per-request settings and catalog caches.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public static function invalidate_request_cache() {
		self::$settings_cache               = null;
		self::$public_post_type_slugs_cache = null;
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
		$custom_css       = isset( $data['custom_css'] ) ? self::sanitize_custom_css( $data['custom_css'] ) : $defaults['custom_css'];

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
			'reader_toc_enabled'          => isset( $data['reader_toc_enabled'] ) ? (bool) $data['reader_toc_enabled'] : $defaults['reader_toc_enabled'],
			'reader_resume_enabled'       => isset( $data['reader_resume_enabled'] ) ? (bool) $data['reader_resume_enabled'] : $defaults['reader_resume_enabled'],
			'preference_controls_enabled' => isset( $data['preference_controls_enabled'] ) ? (bool) $data['preference_controls_enabled'] : $defaults['preference_controls_enabled'],
			'default_reader_theme'        => in_array( $reader_theme, self::get_allowed_reader_themes(), true ) ? $reader_theme : $defaults['default_reader_theme'],
			'default_content_width'       => in_array( $content_width, self::get_allowed_content_widths(), true ) ? $content_width : $defaults['default_content_width'],
			'default_font_size'           => in_array( $font_size, self::get_allowed_font_sizes(), true ) ? $font_size : $defaults['default_font_size'],
			'custom_css'                  => $custom_css,
		];
	}

	/**
	 * Sanitize Reader Mode custom CSS.
	 *
	 * This intentionally does not use safecss_filter_attr(), which is scoped to
	 * inline style attributes rather than complete stylesheet text.
	 *
	 * @since 1.8.0
	 *
	 * @param mixed $css Raw CSS.
	 *
	 * @return string
	 */
	public static function sanitize_custom_css( $css ) {
		$css = is_scalar( $css ) ? (string) $css : '';
		$css = preg_replace( '/^\xEF\xBB\xBF/', '', $css );
		$css = preg_replace( '#^\s*<style\b[^>]*>#i', '', $css );
		$css = preg_replace( '#</style>\s*$#i', '', $css );
		$css = str_replace( "\0", '', $css );
		$css = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $css );

		if ( strlen( $css ) > self::CUSTOM_CSS_MAX_LENGTH ) {
			$css = substr( $css, 0, self::CUSTOM_CSS_MAX_LENGTH );
		}

		return trim( $css );
	}

	/**
	 * Get the required capability for Reader Mode custom CSS editing.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	public static function get_custom_css_capability() {
		/**
		 * Filter the capability required to view and save Reader Mode custom CSS.
		 *
		 * @since 1.8.0
		 *
		 * @param string $capability Required capability.
		 */
		return (string) apply_filters( 'wpdfv_custom_css_capability', 'edit_css' );
	}

	/**
	 * Get saved Reader Mode custom CSS after developer filtering.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	public static function get_custom_css() {
		$settings = self::get_settings();
		$css      = isset( $settings['custom_css'] ) ? self::sanitize_custom_css( $settings['custom_css'] ) : '';

		/**
		 * Filter the final Reader Mode custom CSS before frontend output.
		 *
		 * Return an empty string to disable custom CSS output.
		 *
		 * @since 1.8.0
		 *
		 * @param string $css      Sanitized custom CSS.
		 * @param array  $settings Sanitized Reader Mode settings.
		 */
		return self::sanitize_custom_css( apply_filters( 'wpdfv_custom_css', $css, $settings ) );
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
		$words   = self::count_reading_time_units( $content );

		/**
		 * Filter the content unit count used for Reader Mode estimates.
		 *
		 * @since 1.8.0
		 *
		 * @param int    $words   Counted readable content units.
		 * @param string $content Plain text content used for the estimate.
		 */
		$words = absint( apply_filters( 'wpdfv_reading_time_word_count', $words, $content ) );

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
	 * Count readable content units for reading-time estimates.
	 *
	 * Latin-like words are counted as words, while CJK characters are counted
	 * individually so non-space-delimited content does not collapse to zero.
	 *
	 * @since 1.8.0
	 *
	 * @param string $content Plain text content.
	 *
	 * @return int
	 */
	protected static function count_reading_time_units( $content ) {
		$content = trim( (string) $content );

		if ( '' === $content ) {
			return 0;
		}

		if ( preg_match_all( "/[\\p{Han}\\p{Hiragana}\\p{Katakana}\\p{Hangul}]|[\\p{L}\\p{N}]+(?:['’\\-][\\p{L}\\p{N}]+)*/u", $content, $matches ) ) {
			return count( $matches[0] );
		}

		return str_word_count( $content );
	}

	/**
	 * Sanitize rendered Reader Mode content before returning it to the modal.
	 *
	 * KSES removes disallowed tags but can leave text inside removed script-like
	 * elements behind. Strip those complete element blocks first so shortcode
	 * embed configuration does not become visible reader content.
	 *
	 * @since 1.7.1
	 *
	 * @param string        $content Rendered modal template content.
	 * @param \WP_Post|null $post    Optional post being rendered.
	 *
	 * @return string
	 */
	public static function sanitize_rendered_content( $content, ?\WP_Post $post = null ) {
		$content = (string) $content;

		/**
		 * Filter full element blocks removed from rendered Reader Mode content.
		 *
		 * @since 1.7.1
		 *
		 * @param string[]      $tags Element tag names to remove with their contents.
		 * @param string        $content Rendered modal template content before stripping.
		 * @param \WP_Post|null $post Current post, when available.
		 */
		$tags = apply_filters( 'wpdfv_modal_content_strip_tags', [ 'script', 'style', 'noscript' ], $content, $post );

		if ( is_array( $tags ) ) {
			$tags = array_values(
				array_filter(
					array_map(
						static function ( $tag ) {
							return preg_match( '/^[a-z][a-z0-9:-]*$/i', (string) $tag ) ? (string) $tag : '';
						},
						$tags
					)
				)
			);
		} else {
			$tags = [];
		}

		if ( ! empty( $tags ) ) {
			$content = preg_replace( '#<(' . implode( '|', array_map( 'preg_quote', $tags ) ) . ')\b[^>]*>.*?</\1>#is', '', $content );
		}

		$content           = self::strip_reader_toggle_markup( $content );
		$protected_samples = [];
		$content           = self::protect_visible_code_samples( $content, $protected_samples );
		$content           = self::strip_escaped_executable_blocks( $content, $tags );
		$content           = self::strip_script_text_residue( $content, $post );
		$protected_iframes = [];
		$content           = self::protect_supported_provider_iframes( $content, $protected_iframes );
		$content           = self::restore_protected_samples( $content, $protected_samples );

		/**
		 * Filter rendered Reader Mode content before final KSES sanitization.
		 *
		 * @since 1.7.1
		 *
		 * @param string        $content Rendered modal template content.
		 * @param \WP_Post|null $post Current post, when available.
		 */
		$content = (string) apply_filters( 'wpdfv_modal_content_before_kses', $content, $post );

		$content = wp_kses_post( $content );
		$content = self::restore_supported_provider_iframes( $content, $protected_iframes );

		/**
		 * Filter sanitized Reader Mode content before it is returned by REST.
		 *
		 * @since 1.7.1
		 *
		 * @param string        $content Sanitized Reader Mode content.
		 * @param \WP_Post|null $post Current post, when available.
		 */
		return (string) apply_filters( 'wpdfv_modal_content_after_kses', $content, $post );
	}

	/**
	 * Preserve supported provider embeds through the final KSES pass.
	 *
	 * Reader Mode deliberately does not allow arbitrary iframe markup through
	 * KSES. Trusted provider frames are replaced with inert comment tokens,
	 * sanitized independently, and restored after KSES. Other registered
	 * providers use the accessible fallback link instead.
	 *
	 * @since 1.8.3
	 *
	 * @param string $content   Rendered modal template content.
	 * @param array  $iframes   Protected iframe markup keyed by token.
	 *
	 * @return string
	 */
	protected static function protect_supported_provider_iframes( $content, array &$iframes ) {
		return (string) preg_replace_callback(
			'#<iframe\b([^>]*)>(?:.*?</iframe\s*>)?#is',
			static function ( $matches ) use ( &$iframes ) {
				$attributes = (string) $matches[1];
				$src        = self::get_heading_attribute( $attributes, 'src' );
				$provider   = self::get_provider_from_embed_url( $src );

				if ( '' === $provider ) {
					return $matches[0];
				}

				if ( ! self::is_interactive_provider( $provider ) ) {
					return self::get_provider_fallback_markup( $src, $provider );
				}

				$iframe = self::get_safe_provider_iframe_markup( $attributes, $src, $provider );

				if ( '' === $iframe ) {
					return self::get_provider_fallback_markup( $src, $provider );
				}

				$token             = '<!--wpdfv-provider-iframe-' . count( $iframes ) . '-->';
				$iframes[ $token ] = $iframe;

				return $token;
			},
			(string) $content
		);
	}

	/**
	 * Restore provider iframes after the final KSES pass.
	 *
	 * @since 1.8.3
	 *
	 * @param string $content Rendered modal template content.
	 * @param array  $iframes Protected iframe markup keyed by token.
	 *
	 * @return string
	 */
	protected static function restore_supported_provider_iframes( $content, array $iframes ) {
		return (string) strtr( (string) $content, $iframes );
	}

	/**
	 * Prepare WordPress oEmbed HTML for Reader Mode.
	 *
	 * YouTube and Spotify output is reduced to a safe iframe so provider scripts
	 * never enter the modal script queue. Other registered providers retain the
	 * accessible fallback link used when interactive rendering is unavailable.
	 *
	 * @since 1.8.3
	 *
	 * @param string $html Rendered oEmbed or handler HTML.
	 * @param string $url  Original provider URL.
	 *
	 * @return string
	 */
	public static function filter_supported_embed_html( $html, $url ) {
		if ( ! is_string( $html ) || false === stripos( $html, '<iframe' ) ) {
			return $html;
		}

		$provider = self::get_provider_from_embed_url( $url );

		if ( '' === $provider ) {
			return $html;
		}

		if ( self::is_interactive_provider( $provider ) ) {
			if ( preg_match( '#<iframe\b([^>]*)>(?:.*?</iframe\s*>)?#is', $html, $matches ) ) {
				$iframe_attributes = (string) $matches[1];
				$iframe_src        = self::get_heading_attribute( $iframe_attributes, 'src' );
				$iframe_provider   = self::get_interactive_provider_from_embed_url( $iframe_src );

				if ( $provider === $iframe_provider ) {
					$iframe = self::get_safe_provider_iframe_markup( $iframe_attributes, $iframe_src, $provider );

					if ( '' !== $iframe ) {
						return $iframe;
					}
				}
			}

			return self::get_provider_fallback_markup( $url, $provider );
		}

		return self::get_provider_fallback_markup( $url, $provider );
	}

	/**
	 * Determine whether a provider is allowed to render interactively.
	 *
	 * @since 1.8.3
	 *
	 * @param string $provider Provider label.
	 *
	 * @return bool
	 */
	protected static function is_interactive_provider( $provider ) {
		return in_array( (string) $provider, [ 'YouTube', 'Spotify' ], true );
	}

	/**
	 * Get the interactive provider for an explicitly allowlisted host.
	 *
	 * @since 1.8.3
	 *
	 * @param string $url Embed URL.
	 *
	 * @return string
	 */
	protected static function get_interactive_provider_from_embed_url( $url ) {
		$url    = esc_url_raw( (string) $url );
		$parts  = wp_parse_url( $url );
		$scheme = is_array( $parts ) && isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';
		$host   = is_array( $parts ) && isset( $parts['host'] ) ? strtolower( rtrim( (string) $parts['host'], '.' ) ) : '';

		if ( ! in_array( $scheme, [ '', 'http', 'https' ], true ) || ( '' === $scheme && ! str_starts_with( $url, '//' ) ) ) {
			return '';
		}

		if ( in_array( $host, [ 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be' ], true ) ) {
			return 'YouTube';
		}

		if ( in_array( $host, [ 'open.spotify.com', 'play.spotify.com' ], true ) ) {
			return 'Spotify';
		}

		return '';
	}

	/**
	 * Build a sanitized interactive provider iframe.
	 *
	 * Only presentation and browser permission attributes emitted by the core
	 * provider handlers are retained. Event handlers, inline styles, arbitrary
	 * data attributes, and source markup are intentionally discarded.
	 *
	 * @since 1.8.3
	 *
	 * @param string $attributes Raw iframe attributes.
	 * @param string $src        Provider iframe URL.
	 * @param string $provider   Provider label.
	 *
	 * @return string
	 */
	protected static function get_safe_provider_iframe_markup( $attributes, $src, $provider ) {
		$src = self::get_safe_provider_embed_src( $src );

		if ( '' === $src || ! self::is_interactive_provider( self::get_interactive_provider_from_embed_url( $src ) ) ) {
			return '';
		}

		$defaults = 'Spotify' === $provider
			? [
				'width'  => '100%',
				'height' => '352',
			]
			: [
				'width'  => '560',
				'height' => '315',
			];
		$title    = self::get_heading_attribute( (string) $attributes, 'title' );
		$title    = '' !== $title ? self::sanitize_iframe_text_attribute( $title ) : sprintf( __( '%s embed', 'wp-distraction-free-view' ), $provider );
		$width    = self::sanitize_iframe_dimension( self::get_heading_attribute( (string) $attributes, 'width' ), $defaults['width'] );
		$height   = self::sanitize_iframe_dimension( self::get_heading_attribute( (string) $attributes, 'height' ), $defaults['height'] );
		$loading  = self::get_heading_attribute( (string) $attributes, 'loading' );
		$loading  = in_array( strtolower( $loading ), [ 'auto', 'eager', 'lazy' ], true ) ? strtolower( $loading ) : '';
		$allow    = self::sanitize_iframe_allow_attribute( self::get_heading_attribute( (string) $attributes, 'allow' ) );
		$policy   = self::get_heading_attribute( (string) $attributes, 'referrerpolicy' );
		$policies = [ 'no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url' ];
		$policy   = in_array( strtolower( $policy ), $policies, true ) ? strtolower( $policy ) : '';
		$border   = self::get_heading_attribute( (string) $attributes, 'frameborder' );
		$border   = preg_match( '/^\d{1,2}$/', $border ) ? $border : '';

		$iframe_attributes = [
			'class="wpdfv-reader-provider-embed wpdfv-reader-provider-embed--' . esc_attr( strtolower( $provider ) ) . '"',
			'src="' . esc_attr( $src ) . '"',
			'title="' . esc_attr( $title ) . '"',
			'width="' . esc_attr( $width ) . '"',
			'height="' . esc_attr( $height ) . '"',
		];

		if ( '' !== $loading ) {
			$iframe_attributes[] = 'loading="' . esc_attr( $loading ) . '"';
		}

		if ( '' !== $allow ) {
			$iframe_attributes[] = 'allow="' . esc_attr( $allow ) . '"';
		}

		if ( '' !== $policy ) {
			$iframe_attributes[] = 'referrerpolicy="' . esc_attr( $policy ) . '"';
		}

		if ( '' !== $border ) {
			$iframe_attributes[] = 'frameborder="' . esc_attr( $border ) . '"';
		}

		if ( preg_match( '/(?:^|\s)allowfullscreen(?:\s|=|$)/i', (string) $attributes ) ) {
			$iframe_attributes[] = 'allowfullscreen=""';
		}

		return '<iframe ' . implode( ' ', $iframe_attributes ) . '></iframe>';
	}

	/**
	 * Normalize a provider iframe URL to HTTP(S).
	 *
	 * @since 1.8.3
	 *
	 * @param string $url Provider iframe URL.
	 *
	 * @return string
	 */
	protected static function get_safe_provider_embed_src( $url ) {
		$url    = esc_url_raw( (string) $url );
		$parts  = wp_parse_url( $url );
		$scheme = is_array( $parts ) && isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';

		if ( 'http' === $scheme || 'https' === $scheme ) {
			return $url;
		}

		return '' === $scheme && str_starts_with( $url, '//' ) ? 'https:' . $url : '';
	}

	/**
	 * Sanitize an iframe dimension attribute.
	 *
	 * @since 1.8.3
	 *
	 * @param string $value   Raw dimension.
	 * @param string $fallback Fallback dimension.
	 *
	 * @return string
	 */
	protected static function sanitize_iframe_dimension( $value, $fallback ) {
		$value = trim( (string) $value );

		return preg_match( '/^\d{1,4}%?$/', $value ) ? $value : $fallback;
	}

	/**
	 * Sanitize an iframe allow attribute without permitting markup.
	 *
	 * @since 1.8.3
	 *
	 * @param string $value Raw allow value.
	 *
	 * @return string
	 */
	protected static function sanitize_iframe_allow_attribute( $value ) {
		$value = preg_replace( '/[^a-zA-Z0-9* .;:=_\-]/', '', (string) $value );

		return trim( $value );
	}

	/**
	 * Sanitize a textual iframe attribute.
	 *
	 * @since 1.8.3
	 *
	 * @param string $value Raw attribute value.
	 *
	 * @return string
	 */
	protected static function sanitize_iframe_text_attribute( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]/', ' ', $value );

		return trim( $value );
	}

	/**
	 * Build a safe, labelled link for a supported provider embed.
	 *
	 * @since 1.8.3
	 *
	 * @param string $url      Provider URL.
	 * @param string $provider Provider label.
	 *
	 * @return string
	 */
	protected static function get_provider_fallback_markup( $url, $provider ) {
		$url = esc_url_raw( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		/* translators: %s: embed provider name. */
		$label = sprintf( __( 'Open %s content', 'wp-distraction-free-view' ), $provider );

		return '<p class="wpdfv-provider-embed-fallback"><a href="' . esc_attr( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</a></p>';
	}

	/**
	 * Get the supported provider name for an embed URL.
	 *
	 * @since 1.8.3
	 *
	 * @param string $url Embed URL.
	 *
	 * @return string
	 */
	protected static function get_provider_from_embed_url( $url ) {
		$url     = esc_url_raw( (string) $url );
		$parts   = wp_parse_url( $url );
		$scheme  = is_array( $parts ) && isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';
		$host    = is_array( $parts ) && isset( $parts['host'] ) ? strtolower( rtrim( (string) $parts['host'], '.' ) ) : '';
		$youtube = [ 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be' ];
		$spotify = [ 'open.spotify.com', 'play.spotify.com' ];

		if ( ! in_array( $scheme, [ '', 'http', 'https' ], true ) || ( '' === $scheme && ! str_starts_with( $url, '//' ) ) ) {
			return '';
		}

		if ( in_array( $host, $youtube, true ) ) {
			return 'YouTube';
		}

		if ( in_array( $host, $spotify, true ) ) {
			return 'Spotify';
		}

		if ( function_exists( '_wp_oembed_get_object' ) && class_exists( '\\WP_oEmbed' ) ) {
			$oembed = _wp_oembed_get_object();
			$lookup = $url;

			if ( '' === $scheme && str_starts_with( $url, '//' ) ) {
				$lookup = 'https:' . $url;
			}

			if ( $oembed instanceof \WP_oEmbed ) {
				$provider = $oembed->get_provider( $lookup, [ 'discover' => false ] );

				if ( false !== $provider ) {
					$provider_parts = wp_parse_url( (string) $provider );
					$provider_host  = is_array( $provider_parts ) && isset( $provider_parts['host'] ) ? strtolower( rtrim( (string) $provider_parts['host'], '.' ) ) : '';

					return self::get_provider_label( $host, $provider_host );
				}
			}
		}

		return '';
	}

	/**
	 * Get a readable provider label from a provider hostname.
	 *
	 * @since 1.8.3
	 *
	 * @param string $host          Provider hostname.
	 * @param string $fallback_host Registered provider endpoint hostname.
	 *
	 * @return string
	 */
	protected static function get_provider_label( $host, $fallback_host = '' ) {
		$hosts = array_values(
			array_filter(
				array_unique(
					array_map(
						static function ( $value ) {
							$value = strtolower( trim( (string) $value ) );
							return preg_replace( '/^(?:www|m|open|play|embed|player|assets)\./', '', $value );
						},
						[ $host, $fallback_host ]
					)
				)
			)
		);

		$labels = [
			'a.co'                 => 'Amazon',
			'amazon.com'           => 'Amazon',
			'amzn.asia'            => 'Amazon',
			'amzn.eu'              => 'Amazon',
			'amzn.in'              => 'Amazon',
			'amzn.to'              => 'Amazon',
			'anghami.com'          => 'Anghami',
			'animoto.com'          => 'Animoto',
			'bsky.app'             => 'Bluesky',
			'canva.com'            => 'Canva',
			'cloudup.com'          => 'Cloudup',
			'crowdsignal.net'      => 'Crowdsignal',
			'dai.ly'               => 'Dailymotion',
			'dailymotion.com'      => 'Dailymotion',
			'flic.kr'              => 'Flickr',
			'flickr.com'           => 'Flickr',
			/* phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Provider hostname, not a remote asset. */
			'imgur.com'            => 'Imgur',
			'issuu.com'            => 'Issuu',
			'kck.st'               => 'Kickstarter',
			'kickstarter.com'      => 'Kickstarter',
			'mixcloud.com'         => 'Mixcloud',
			'pca.st'               => 'Pocket Casts',
			'pocketcasts.com'      => 'Pocket Casts',
			'pinterest.com'        => 'Pinterest',
			'polldaddy.com'        => 'Crowdsignal',
			'poll.fm'              => 'Crowdsignal',
			'reddit.com'           => 'Reddit',
			'reverbnation.com'     => 'ReverbNation',
			'scribd.com'           => 'Scribd',
			'smugmug.com'          => 'SmugMug',
			'some.ly'              => 'Someecards',
			'someecards.com'       => 'Someecards',
			'soundcloud.com'       => 'SoundCloud',
			'speakerdeck.com'      => 'Speaker Deck',
			'survey.fm'            => 'Crowdsignal',
			'ted.com'              => 'TED',
			'tiktok.com'           => 'TikTok',
			'tumblr.com'           => 'Tumblr',
			'twitter.com'          => 'Twitter',
			'spotify.com'          => 'Spotify',
			'vimeo.com'            => 'Vimeo',
			'video214.com'         => 'Animoto',
			'videopress.com'       => 'VideoPress',
			'wolframcloud.com'     => 'WolframCloud',
			'wordpress.tv'         => 'WordPress.tv',
			'youtu.be'             => 'YouTube',
			'youtube-nocookie.com' => 'YouTube',
			'youtube.com'          => 'YouTube',
			'z.cn'                 => 'Amazon',
		];

		foreach ( $hosts as $candidate ) {
			foreach ( $labels as $domain => $label ) {
				if ( $candidate === $domain || str_ends_with( $candidate, '.' . $domain ) ) {
					return $label;
				}
			}
		}

		$parts = explode( '.', $hosts[0] ?? '' );
		$label = count( $parts ) > 1 ? $parts[ count( $parts ) - 2 ] : ( $hosts[0] ?? $host );

		return ucwords( str_replace( [ '-', '_' ], ' ', $label ) );
	}

	/**
	 * Remove WPDFV Reader Mode launch controls from rendered modal content.
	 *
	 * Manual shortcode and block placement should remain on the source page,
	 * but the modal should never render another Reader Mode launch control
	 * inside the reading surface.
	 *
	 * @since 1.8.0
	 *
	 * @param string $content Rendered modal template content.
	 *
	 * @return string
	 */
	protected static function strip_reader_toggle_markup( $content ) {
		return (string) preg_replace(
			'#<div\b[^>]*class=(["\'])[^"\']*\bwpdfv-fullscreen-container\b[^"\']*\1[^>]*>\s*<button\b[^>]*class=(["\'])[^"\']*\bwpdfv-reader-toggle\b[^"\']*\2[^>]*>.*?</button>\s*</div>#is',
			'',
			(string) $content
		);
	}

	/**
	 * Prepare rendered Reader Mode content and executable scripts for the modal.
	 *
	 * Scripts inserted through shortcode output need to run after the modal HTML
	 * is mounted. Extracting them before KSES keeps script bodies out of visible
	 * reader text while still allowing compatible shortcode embeds to initialize.
	 *
	 * @since 1.7.1
	 *
	 * @param string        $content Rendered modal template content.
	 * @param \WP_Post|null $post    Optional post being rendered.
	 *
	 * @return array{content:string,scripts:array,toc:array}
	 */
	public static function prepare_rendered_content( $content, ?\WP_Post $post = null ) {
		$prepared = self::extract_rendered_scripts( (string) $content, $post );
		$content  = self::sanitize_rendered_content( $prepared['content'], $post );
		$toc      = self::prepare_table_of_contents( $content );

		return [
			'content' => $toc['content'],
			'scripts' => $prepared['scripts'],
			'toc'     => $toc['items'],
		];
	}

	/**
	 * Build Reader Mode table of contents items from rendered headings.
	 *
	 * Generated IDs are applied only to the REST-rendered Reader Mode markup.
	 * Stored post content is never changed.
	 *
	 * @since 1.8.0
	 *
	 * @param string $content Sanitized rendered modal content.
	 *
	 * @return array{content:string,items:array}
	 */
	public static function prepare_table_of_contents( $content ) {
		$content = (string) $content;
		$items   = [];
		$used    = [];

		$content = (string) preg_replace_callback(
			'#<h([1-6])\b([^>]*)>(.*?)</h\1>#is',
			static function ( $matches ) use ( &$items, &$used ) {
				$level      = absint( $matches[1] );
				$attributes = (string) $matches[2];
				$inner_html = (string) $matches[3];
				$text       = trim( wp_strip_all_tags( html_entity_decode( $inner_html, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );

				if ( '' === $text ) {
					return $matches[0];
				}

				$existing_id = self::get_heading_attribute( $attributes, 'id' );
				$base_id     = '' !== $existing_id ? sanitize_title( strtolower( $existing_id ) ) : sanitize_title( strtolower( $text ) );

				if ( '' === $base_id ) {
					$base_id = 'reader-heading';
				}

				$id = self::get_unique_heading_id( $base_id, $used );

				$items[] = [
					'id'    => $id,
					'level' => $level,
					'text'  => $text,
				];

				$attributes = self::set_heading_id_attribute( $attributes, $id );

				return '<h' . $level . $attributes . '>' . $inner_html . '</h' . $level . '>';
			},
			$content
		);

		return [
			'content' => $content,
			'items'   => $items,
		];
	}

	/**
	 * Get a heading attribute value from raw heading attributes.
	 *
	 * @since 1.8.0
	 *
	 * @param string $attributes Raw attribute string.
	 * @param string $name       Attribute name.
	 *
	 * @return string
	 */
	protected static function get_heading_attribute( $attributes, $name ) {
		if ( ! preg_match( '/(?:^|\s)' . preg_quote( $name, '/' ) . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', $attributes, $match ) ) {
			return '';
		}

		foreach ( [ 1, 2, 3 ] as $index ) {
			if ( isset( $match[ $index ] ) && '' !== $match[ $index ] ) {
				return html_entity_decode( $match[ $index ], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
		}

		return '';
	}

	/**
	 * Return an ID that is unique within the rendered Reader Mode document.
	 *
	 * @since 1.8.0
	 *
	 * @param string $base_id Base heading ID.
	 * @param array  $used    Used IDs keyed by ID.
	 *
	 * @return string
	 */
	protected static function get_unique_heading_id( $base_id, array &$used ) {
		$id     = $base_id;
		$suffix = 2;

		while ( isset( $used[ $id ] ) ) {
			$id = $base_id . '-' . $suffix;
			++$suffix;
		}

		$used[ $id ] = true;

		return $id;
	}

	/**
	 * Add or replace a heading ID attribute.
	 *
	 * @since 1.8.0
	 *
	 * @param string $attributes Raw attribute string.
	 * @param string $id         Safe heading ID.
	 *
	 * @return string
	 */
	protected static function set_heading_id_attribute( $attributes, $id ) {
		$id = esc_attr( $id );

		if ( preg_match( '/(?:^|\s)id\s*=/i', $attributes ) ) {
			return preg_replace( '/\s*id\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+)/i', ' id="' . $id . '"', $attributes, 1 );
		}

		return rtrim( $attributes ) . ' id="' . $id . '"';
	}

	/**
	 * Extract script tags from rendered content before final sanitization.
	 *
	 * @since 1.7.1
	 *
	 * @param string        $content Rendered modal template content.
	 * @param \WP_Post|null $post    Optional post being rendered.
	 *
	 * @return array{content:string,scripts:array}
	 */
	protected static function extract_rendered_scripts( $content, ?\WP_Post $post = null ) {
		$scripts = [];
		$content = (string) preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script>#is',
			static function ( $matches ) use ( &$scripts ) {
				$script = self::normalize_rendered_script( $matches[1], $matches[2] );

				if ( ! empty( $script ) ) {
					$scripts[] = $script;
				}

				return '';
			},
			$content
		);

		/**
		 * Filter scripts extracted from rendered Reader Mode content.
		 *
		 * Returning an empty array disables modal script execution. Scripts are
		 * still removed from visible content before the REST response is sent.
		 *
		 * @since 1.7.1
		 *
		 * @param array          $scripts Extracted script definitions.
		 * @param \WP_Post|null $post    Current post, when available.
		 */
		$scripts = apply_filters( 'wpdfv_modal_content_scripts', $scripts, $post );

		return [
			'content' => $content,
			'scripts' => self::sanitize_extracted_scripts( $scripts ),
		];
	}

	/**
	 * Normalize one extracted script tag.
	 *
	 * @since 1.7.1
	 *
	 * @param string $attributes Raw script tag attributes.
	 * @param string $content    Raw script contents.
	 *
	 * @return array
	 */
	protected static function normalize_rendered_script( $attributes, $content ) {
		$attributes = self::parse_script_attributes( $attributes );
		$script     = [
			'attributes' => $attributes,
			'content'    => (string) $content,
		];

		if ( ! empty( $attributes['src'] ) ) {
			$script['src'] = $attributes['src'];
		}

		if ( ! empty( $attributes['type'] ) ) {
			$script['type'] = $attributes['type'];
		}

		return $script;
	}

	/**
	 * Parse script tag attributes into a safe associative array.
	 *
	 * @since 1.7.1
	 *
	 * @param string $attributes Raw script tag attributes.
	 *
	 * @return array
	 */
	protected static function parse_script_attributes( $attributes ) {
		if ( '' === trim( (string) $attributes ) ) {
			return [];
		}

		preg_match_all(
			'/([^\s\/=<>]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/',
			(string) $attributes,
			$matches,
			PREG_SET_ORDER
		);

		$parsed = [];

		foreach ( $matches as $match ) {
			$name = strtolower( (string) $match[1] );

			if ( ! self::is_allowed_script_attribute( $name ) ) {
				continue;
			}

			$value = true;

			if ( array_key_exists( 2, $match ) && '' !== $match[2] ) {
				$value = html_entity_decode( $match[2], ENT_QUOTES, get_bloginfo( 'charset' ) );
			} elseif ( array_key_exists( 3, $match ) && '' !== $match[3] ) {
				$value = html_entity_decode( $match[3], ENT_QUOTES, get_bloginfo( 'charset' ) );
			} elseif ( array_key_exists( 4, $match ) && '' !== $match[4] ) {
				$value = html_entity_decode( $match[4], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			$parsed[ $name ] = self::sanitize_script_attribute_value( $name, $value );
		}

		return array_filter(
			$parsed,
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * Determine whether a script attribute can be replayed in the modal.
	 *
	 * @since 1.7.1
	 *
	 * @param string $name Attribute name.
	 *
	 * @return bool
	 */
	protected static function is_allowed_script_attribute( $name ) {
		if ( str_starts_with( $name, 'data-' ) ) {
			return true;
		}

		return in_array(
			$name,
			[
				'async',
				'charset',
				'class',
				'crossorigin',
				'defer',
				'id',
				'integrity',
				'nonce',
				'nomodule',
				'referrerpolicy',
				'src',
				'type',
			],
			true
		);
	}

	/**
	 * Sanitize a replayed script attribute value.
	 *
	 * @since 1.7.1
	 *
	 * @param string      $name  Attribute name.
	 * @param string|bool $value Attribute value.
	 *
	 * @return string|bool|null
	 */
	protected static function sanitize_script_attribute_value( $name, $value ) {
		if ( in_array( $name, [ 'async', 'defer', 'nomodule' ], true ) ) {
			return true;
		}

		if ( true === $value ) {
			return null;
		}

		if ( 'src' === $name ) {
			$src = esc_url_raw( (string) $value );

			return '' !== $src ? $src : null;
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize extracted script definitions after filters run.
	 *
	 * @since 1.7.1
	 *
	 * @param mixed $scripts Extracted script definitions.
	 *
	 * @return array
	 */
	protected static function sanitize_extracted_scripts( $scripts ) {
		if ( ! is_array( $scripts ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $scripts as $script ) {
			if ( ! is_array( $script ) ) {
				continue;
			}

			$raw_attributes = isset( $script['attributes'] ) && is_array( $script['attributes'] ) ? $script['attributes'] : [];
			$attributes     = [];

			foreach ( $raw_attributes as $name => $value ) {
				$name = strtolower( (string) $name );

				if ( ! self::is_allowed_script_attribute( $name ) ) {
					continue;
				}

				$value = self::sanitize_script_attribute_value( $name, $value );

				if ( null !== $value ) {
					$attributes[ $name ] = $value;
				}
			}

			$src = isset( $script['src'] ) ? esc_url_raw( (string) $script['src'] ) : '';

			if ( '' !== $src ) {
				$attributes['src'] = $src;
			}

			$next = [
				'attributes' => $attributes,
				'content'    => isset( $script['content'] ) ? (string) $script['content'] : '',
			];

			if ( ! empty( $attributes['src'] ) ) {
				$next['src'] = $attributes['src'];
			}

			if ( ! empty( $attributes['type'] ) ) {
				$next['type'] = $attributes['type'];
			}

			if ( '' === $next['content'] && empty( $next['src'] ) ) {
				continue;
			}

			$sanitized[] = $next;
		}

		return $sanitized;
	}

	/**
	 * Protect visible code examples before stripping escaped script residue.
	 *
	 * Reader Mode should remove executable/embed setup fragments, but should not
	 * hide intentional code samples that authors display in pre/code elements.
	 *
	 * @since 1.7.1
	 *
	 * @param string $content Rendered modal template content.
	 * @param array  $samples Protected samples keyed by placeholder.
	 *
	 * @return string
	 */
	protected static function protect_visible_code_samples( $content, array &$samples ) {
		return (string) preg_replace_callback(
			'#<(pre|code)\b[^>]*>.*?</\1>#is',
			static function ( $matches ) use ( &$samples ) {
				$placeholder             = '%%WPDFV_PROTECTED_CODE_SAMPLE_' . count( $samples ) . '%%';
				$samples[ $placeholder ] = $matches[0];

				return $placeholder;
			},
			$content
		);
	}

	/**
	 * Restore visible code examples after script residue stripping.
	 *
	 * @since 1.7.1
	 *
	 * @param string $content Rendered modal template content.
	 * @param array  $samples Protected samples keyed by placeholder.
	 *
	 * @return string
	 */
	protected static function restore_protected_samples( $content, array $samples ) {
		return ! empty( $samples ) ? strtr( $content, $samples ) : $content;
	}

	/**
	 * Strip escaped executable blocks that would otherwise render as raw text.
	 *
	 * Some embed plugins escape their inline setup scripts before Reader Mode
	 * receives the rendered content. KSES cannot remove those because they are
	 * already text, so remove escaped script-like blocks outside visible code.
	 *
	 * @since 1.7.1
	 *
	 * @param string $content Rendered modal template content.
	 * @param array  $tags    Element tag names to remove with their contents.
	 *
	 * @return string
	 */
	protected static function strip_escaped_executable_blocks( $content, array $tags ) {
		if ( empty( $tags ) ) {
			return $content;
		}

		$tag_pattern   = implode(
			'|',
			array_map(
				static function ( $tag ) {
					return preg_quote( $tag, '~' );
				},
				$tags
			)
		);
		$escaped_lt    = '(?:&lt;|&#0*60;?|&#x0*3c;?)';
		$escaped_gt    = '(?:&gt;|&#0*62;?|&#x0*3e;?)';
		$escaped_slash = '(?:/|&#0*47;?|&#x0*2f;?)';

		return (string) preg_replace(
			'~' . $escaped_lt . '\s*(' . $tag_pattern . ')\b.*?' . $escaped_gt . '.*?' . $escaped_lt . '\s*' . $escaped_slash . '\s*\1\s*' . $escaped_gt . '~is',
			'',
			$content
		);
	}

	/**
	 * Strip standalone JavaScript setup residue that no longer has script tags.
	 *
	 * @since 1.7.1
	 *
	 * @param string        $content Rendered modal template content.
	 * @param \WP_Post|null $post    Optional post being rendered.
	 *
	 * @return string
	 */
	protected static function strip_script_text_residue( $content, ?\WP_Post $post = null ) {
		$patterns = self::get_script_text_residue_patterns( $post );

		if ( empty( $patterns ) ) {
			return $content;
		}

		$content = self::strip_script_text_residue_elements( $content, '#<(p|span)\b[^>]*>.*?</\1>#is', $patterns );
		$content = self::strip_script_text_residue_elements( $content, '#<div\b[^>]*>(?:(?!</?div\b).)*</div>#is', $patterns, 'div' );
		$content = self::strip_script_text_residue_text_nodes( $content, $patterns );

		return (string) preg_replace_callback(
			'~(^|[\r\n])([^\r\n]*(?:window\.option_df_|window\.DFLIP|DFLIP\.parseBooks)[^\r\n]*)(?=[\r\n]|$)~i',
			static function ( $matches ) use ( $patterns ) {
				if ( str_contains( $matches[2], '<' ) || str_contains( $matches[2], '>' ) ) {
					return $matches[0];
				}

				return self::looks_like_script_text_residue( $matches[2], $patterns ) ? $matches[1] : $matches[0];
			},
			$content
		);
	}

	/**
	 * Strip script residue from standalone HTML elements.
	 *
	 * @since 1.7.1
	 *
	 * @param string   $content  Rendered modal template content.
	 * @param string      $pattern      Element-matching regular expression.
	 * @param string[]    $patterns     Regular expressions used to identify script residue.
	 * @param string|null $element_name Matched element name, when the pattern does not capture it.
	 *
	 * @return string
	 */
	protected static function strip_script_text_residue_elements( $content, $pattern, array $patterns, $element_name = null ) {
		return (string) preg_replace_callback(
			$pattern,
			static function ( $matches ) use ( $patterns, $element_name ) {
				$tag_name = null !== $element_name ? (string) $element_name : strtolower( (string) ( $matches[1] ?? '' ) );

				if ( 'div' === $tag_name && self::contains_reader_content_blocks( $matches[0] ) ) {
					return $matches[0];
				}

				return self::looks_like_script_text_residue( $matches[0], $patterns ) ? '' : $matches[0];
			},
			$content
		);
	}

	/**
	 * Determine whether a wrapper contains normal reader content blocks.
	 *
	 * A rendered post-content div can also contain escaped embed setup text.
	 * That residue should be removed, but the wrapper itself must remain.
	 *
	 * @since 1.8.0
	 *
	 * @param string $content Content fragment.
	 *
	 * @return bool
	 */
	protected static function contains_reader_content_blocks( $content ) {
		return (bool) preg_match(
			'#<(p|h[1-6]|blockquote|figure|figcaption|ul|ol|li|table|thead|tbody|tfoot|tr|td|th|hr|pre|code|article|section)\b#i',
			(string) $content
		);
	}

	/**
	 * Strip standalone script residue that appears as text between elements.
	 *
	 * @since 1.8.0
	 *
	 * @param string   $content  Rendered modal template content.
	 * @param string[] $patterns Regular expressions used to identify script residue.
	 *
	 * @return string
	 */
	protected static function strip_script_text_residue_text_nodes( $content, array $patterns ) {
		return (string) preg_replace_callback(
			'~(^|>)([^<>]*(?:window\.option_df_|window\.DFLIP|DFLIP\.parseBooks)[^<>]*)(?=<|$)~i',
			static function ( $matches ) use ( $patterns ) {
				return self::looks_like_script_text_residue( $matches[2], $patterns ) ? $matches[1] : $matches[0];
			},
			$content
		);
	}

	/**
	 * Get patterns used to detect inline embed setup residue.
	 *
	 * @since 1.7.1
	 *
	 * @param \WP_Post|null $post Optional post being rendered.
	 *
	 * @return string[]
	 */
	protected static function get_script_text_residue_patterns( ?\WP_Post $post = null ) {
		$patterns = [
			'/\bwindow\.option_df_[a-z0-9_]+\s*=/i',
			'/\bwindow\.DFLIP\b/i',
			'/\bDFLIP\.parseBooks\s*\(/i',
		];

		/**
		 * Filter text patterns removed from rendered Reader Mode content.
		 *
		 * This filter is intentionally narrower than the element-strip filter:
		 * it only handles embed setup code that has already become visible text.
		 *
		 * @since 1.7.1
		 *
		 * @param string[]      $patterns Regular expressions used to identify script residue.
		 * @param \WP_Post|null $post     Current post, when available.
		 */
		$patterns = apply_filters( 'wpdfv_modal_content_script_text_patterns', $patterns, $post );

		if ( ! is_array( $patterns ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					static function ( $pattern ) {
						return is_string( $pattern ) && '' !== $pattern ? $pattern : '';
					},
					$patterns
				)
			)
		);
	}

	/**
	 * Determine whether a text fragment is script setup residue.
	 *
	 * @since 1.7.1
	 *
	 * @param string   $content  Content fragment.
	 * @param string[] $patterns Regular expressions used to identify script residue.
	 *
	 * @return bool
	 */
	protected static function looks_like_script_text_residue( $content, array $patterns ) {
		$text = trim( wp_strip_all_tags( html_entity_decode( $content, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );

		if ( '' === $text ) {
			return false;
		}

		foreach ( $patterns as $pattern ) {
			$result = @preg_match( $pattern, $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid filtered patterns are ignored below.

			if ( 1 === $result ) {
				return true;
			}
		}

		return false;
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
		if ( null !== self::$public_post_type_slugs_cache ) {
			return self::$public_post_type_slugs_cache;
		}

		if ( ! function_exists( 'get_post_types' ) ) {
			self::$public_post_type_slugs_cache = [ 'post', 'page' ];

			return self::$public_post_type_slugs_cache;
		}

		self::$public_post_type_slugs_cache = array_keys( get_post_types( [ 'public' => true ], 'objects' ) );

		return self::$public_post_type_slugs_cache;
	}
}
