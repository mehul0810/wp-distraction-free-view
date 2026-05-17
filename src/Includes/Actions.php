<?php
/**
 * WP Distraction Free View | Frontend Actions.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_footer', [ $this, 'render_floating_button' ] );
	}

	/**
	 * Register frontend assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets() {
		if ( ! $this->should_enqueue_assets() ) {
			return;
		}

		$asset_path = WPDFV_PLUGIN_DIR . 'assets/dist/js/wpdfv.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : [
			'dependencies' => [ 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ],
			'version'      => WPDFV_VERSION,
		];

		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/wpdfv.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_enqueue_script(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'wpdfv-core', 'wp-distraction-free-view', WPDFV_PLUGIN_DIR . 'languages' );
		wp_add_inline_script( 'wpdfv-core', 'window.wpdfvReaderMode = ' . wp_json_encode( $this->get_frontend_settings() ) . ';', 'before' );
	}

	/**
	 * Determine whether frontend assets are needed on the current page.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function should_enqueue_assets() {
		$should_enqueue = false;
		$post           = get_post();

		if ( $post instanceof \WP_Post ) {
			$has_shortcode    = $this->has_reader_shortcode( $post->post_content );
			$is_reader_post   = $this->is_supported_reader_context( $post );
			$display_location = Reader::display_location();

			$should_enqueue = $has_shortcode || ( Reader::is_reader_mode_request() && $is_reader_post ) || ( Reader::is_automatic_button_enabled() && 'manual_only' !== $display_location && $is_reader_post );
		}

		/**
		 * Filter whether WP Distraction Free View frontend assets should load.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $should_enqueue Whether assets should load.
		 */
		return (bool) apply_filters( 'wpdfv_should_enqueue_frontend_assets', $should_enqueue );
	}

	/**
	 * Render a floating automatic Reader Mode toggle when enabled.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function render_floating_button() {
		if ( ! Reader::is_automatic_button_enabled() || 'floating' !== Reader::display_location() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post || ! $this->is_supported_reader_context( $post ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped static markup.
		echo Helpers::display_read_mode_button( $post->ID, '', 'class="wpdfv-fullscreen-container wpdfv-fullscreen-container--floating"' );
	}

	/**
	 * Determine whether content has a supported reader shortcode.
	 *
	 * @since 2.2.0
	 *
	 * @param string $content Post content.
	 *
	 * @return bool
	 */
	protected function has_reader_shortcode( $content ) {
		return has_shortcode( $content, 'wpdfv' ) || has_shortcode( $content, 'wpdfv_reader_toggle' ) || has_shortcode( $content, 'dfview' );
	}

	/**
	 * Determine whether Reader Mode is enabled for the current singular post.
	 *
	 * @since 2.2.0
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return bool
	 */
	protected function is_supported_reader_context( \WP_Post $post ) {
		if ( function_exists( 'is_singular' ) && ! is_singular( $post->post_type ) ) {
			return false;
		}

		return Reader::is_post_type_enabled( $post->post_type );
	}

	/**
	 * Get settings exposed to the frontend reader app.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	protected function get_frontend_settings() {
		$settings = Reader::get_settings();
		$post     = get_post();

		return [
			'currentPostId'             => $post instanceof \WP_Post ? $post->ID : 0,
			'autoOpen'                  => Reader::is_reader_mode_request(),
			'exitButtonText'            => $settings['exit_button_text'],
			'readingProgressEnabled'    => $settings['reading_progress_enabled'],
			'readingTimeEnabled'        => $settings['reading_time_enabled'],
			'preferenceControlsEnabled' => $settings['preference_controls_enabled'],
			'defaultReaderTheme'        => $settings['default_reader_theme'],
			'defaultContentWidth'       => $settings['default_content_width'],
			'defaultFontSize'           => $settings['default_font_size'],
			'preferencesStorageKey'     => Reader::PREFERENCES_STORAGE_KEY,
			'readerModeQueryParam'      => Reader::QUERY_PARAM,
		];
	}
}
