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
	 * Whether frontend configuration has already been attached to the script.
	 *
	 * @since 1.7.0
	 *
	 * @var bool
	 */
	private static $frontend_settings_added = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', [ __CLASS__, 'register_frontend_assets' ], 5 );
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

		self::enqueue_frontend_assets();
	}

	/**
	 * Register frontend assets so dynamic blocks can reuse the same handles.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public static function register_frontend_assets() {
		$asset_path = WPDFV_PLUGIN_DIR . 'assets/dist/js/wpdfv.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : [
			'dependencies' => [ 'wp-api-fetch', 'wp-element', 'wp-i18n', 'wp-primitives' ],
			'version'      => WPDFV_VERSION,
		];

		wp_register_style(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/wpdfv.css',
			[],
			$asset['version']
		);

		wp_register_script(
			'wpdfv-core',
			WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'wpdfv-core', 'wp-distraction-free-view', WPDFV_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Enqueue the shared frontend reader assets and attach runtime settings.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets() {
		self::register_frontend_assets();

		wp_enqueue_style( 'wpdfv-core' );
		wp_enqueue_script( 'wpdfv-core' );
		self::enqueue_reader_interactivity_modules();
		self::add_custom_css();

		if ( self::$frontend_settings_added ) {
			return;
		}

		wp_add_inline_script( 'wpdfv-core', 'window.wpdfvReaderMode = ' . wp_json_encode( self::get_frontend_settings() ) . ';', 'before' );
		self::$frontend_settings_added = true;
	}

	/**
	 * Attach Reader Mode custom CSS to the frontend stylesheet handle.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	protected static function add_custom_css() {
		$custom_css = Reader::get_custom_css();

		if ( '' === $custom_css ) {
			return;
		}

		wp_add_inline_style( 'wpdfv-core', $custom_css );
	}

	/**
	 * Enqueue core script modules needed by interactive blocks in Reader Mode.
	 *
	 * Reader Mode content is mounted into the modal after the page has loaded,
	 * so blocks using the Interactivity API need their view modules available
	 * before the frontend app hydrates inserted modal markup.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	protected static function enqueue_reader_interactivity_modules() {
		if ( ! function_exists( 'wp_enqueue_script_module' ) ) {
			return;
		}

		call_user_func(
			'wp_enqueue_script_module',
			'@wordpress/block-library/accordion/view'
		);
	}

	/**
	 * Determine whether frontend assets are needed on the current page.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	protected function should_enqueue_assets() {
		$should_enqueue = false;
		$post           = get_post();

		if ( $post instanceof \WP_Post ) {
			$has_shortcode    = has_shortcode( $post->post_content, 'wpdfv' );
			$is_reader_post   = $this->is_supported_reader_context( $post );
			$display_location = Reader::display_location();

			$should_enqueue = $has_shortcode || ( Reader::is_reader_mode_request() && $is_reader_post ) || ( Reader::is_automatic_button_enabled() && 'manual_only' !== $display_location && $is_reader_post );
		}

		/**
		 * Filter whether WP Distraction Free View frontend assets should load.
		 *
		 * @since 1.7.0
		 *
		 * @param bool $should_enqueue Whether assets should load.
		 */
		return (bool) apply_filters( 'wpdfv_should_enqueue_frontend_assets', $should_enqueue );
	}

	/**
	 * Render a floating automatic Reader Mode toggle when enabled.
	 *
	 * @since 1.7.0
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
	 * Determine whether Reader Mode is enabled for the current singular post.
	 *
	 * @since 1.7.0
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
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected static function get_frontend_settings() {
		$settings = Reader::get_settings();
		$post     = get_post();

		return [
			'currentPostId'             => $post instanceof \WP_Post ? $post->ID : 0,
			'autoOpen'                  => Reader::is_reader_mode_request(),
			'exitButtonText'            => $settings['exit_button_text'],
			'readingProgressEnabled'    => $settings['reading_progress_enabled'],
			'readingTimeEnabled'        => $settings['reading_time_enabled'],
			'readerTocEnabled'          => $settings['reader_toc_enabled'],
			'readerResumeEnabled'       => $settings['reader_resume_enabled'],
			'preferenceControlsEnabled' => $settings['preference_controls_enabled'],
			'defaultReaderTheme'        => $settings['default_reader_theme'],
			'defaultContentWidth'       => $settings['default_content_width'],
			'defaultFontSize'           => $settings['default_font_size'],
			'preferencesStorageKey'     => Reader::PREFERENCES_STORAGE_KEY,
			'positionsStorageKey'       => Reader::POSITIONS_STORAGE_KEY,
			'readerModeQueryParam'      => Reader::QUERY_PARAM,
		];
	}
}
