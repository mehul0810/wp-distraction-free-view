<?php
/**
 * WP Distraction Free View | Modal templates.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Templates {
	/**
	 * Registered template cache for the current request.
	 *
	 * @since 1.8.0
	 *
	 * @var array|null
	 */
	protected static $registered_templates_cache = null;

	/**
	 * Template options cache for the current request.
	 *
	 * @since 1.8.0
	 *
	 * @var array|null
	 */
	protected static $template_options_cache = null;

	/**
	 * Default modal template slug.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	const DEFAULT_TEMPLATE = 'default';

	/**
	 * Block pattern category for modal templates.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	const PATTERN_CATEGORY = 'wpdfv-modal-templates';

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_patterns' ] );
	}

	/**
	 * Register block pattern category and built-in modal templates.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function register_patterns() {
		if ( function_exists( 'register_block_pattern_category' ) && ! $this->is_pattern_category_registered() ) {
			register_block_pattern_category(
				self::PATTERN_CATEGORY,
				[
					'label' => __( 'WP Distraction Free View', 'wp-distraction-free-view' ),
				]
			);
		}

		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		foreach ( self::get_registered_templates() as $slug => $template ) {
			if (
				function_exists( 'register_block_pattern_category' ) &&
				self::PATTERN_CATEGORY !== $template['category'] &&
				! $this->is_pattern_category_registered( $template['category'] )
			) {
				register_block_pattern_category(
					$template['category'],
					[
						'label' => sanitize_text_field( ucwords( str_replace( [ '-', '_' ], ' ', $template['category'] ) ) ),
					]
				);
			}

			$pattern_name = sprintf( 'wpdfv/%s-modal-template', sanitize_title( $slug ) );

			if ( $this->is_pattern_registered( $pattern_name ) ) {
				continue;
			}

			register_block_pattern(
				$pattern_name,
				[
					'title'       => $template['label'],
					'description' => $template['description'],
					'content'     => $template['content'],
					'categories'  => [ $template['category'] ],
				]
			);
		}
	}

	/**
	 * Get modal template options for settings UI and rendering.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_template_options() {
		if ( null !== self::$template_options_cache ) {
			return self::$template_options_cache;
		}

		$options = [];

		foreach ( self::get_registered_templates() as $slug => $template ) {
			$options[] = [
				'label'       => $template['label'],
				'value'       => $slug,
				'description' => $template['description'],
				'category'    => $template['category'],
				'preview'     => $template['preview'],
			];
		}

		self::$template_options_cache = $options;

		return self::$template_options_cache;
	}

	/**
	 * Clear per-request modal template caches.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public static function invalidate_request_cache() {
		self::$registered_templates_cache = null;
		self::$template_options_cache     = null;
	}

	/**
	 * Render the selected block-based modal template for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post $post Post to render.
	 *
	 * @return string
	 */
	public static function render_modal_content( \WP_Post $post ) {
		$templates     = self::get_registered_templates();
		$template_slug = self::get_selected_template_slug( $post );
		$template      = $templates[ $template_slug ] ?? $templates[ self::DEFAULT_TEMPLATE ];
		$previous_post = $GLOBALS['post'] ?? null;

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for core post blocks in the modal template.
		setup_postdata( $post );

		try {
			$embed_filter = [ Reader::class, 'filter_supported_embed_html' ];
			add_filter( 'embed_oembed_html', $embed_filter, 10, 4 );
			add_filter( 'embed_handler_html', $embed_filter, 10, 3 );

			$content = Helpers::without_button_injection(
				static function () use ( $template ) {
					return do_blocks( $template['content'] );
				}
			);
		} finally {
			remove_filter( 'embed_oembed_html', $embed_filter, 10 );
			remove_filter( 'embed_handler_html', $embed_filter, 10 );
			wp_reset_postdata();

			if ( $previous_post instanceof \WP_Post ) {
				$GLOBALS['post'] = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring previous global post.
			} elseif ( null === $previous_post ) {
				unset( $GLOBALS['post'] );
			}
		}

		/**
		 * Filter the rendered modal template content.
		 *
		 * @since 1.7.0
		 *
		 * @param string   $content       Rendered modal template content.
		 * @param \WP_Post $post          Current post.
		 * @param string   $template_slug Selected template slug.
		 * @param array    $template      Template definition.
		 */
		return (string) apply_filters( 'wpdfv_modal_template_content', $content, $post, $template_slug, $template );
	}

	/**
	 * Get the selected modal template slug from settings.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public static function get_selected_template_slug( ?\WP_Post $post = null ) {
		$settings = Helpers::get_settings();
		$slug     = isset( $settings['modal_template'] ) ? sanitize_key( $settings['modal_template'] ) : self::DEFAULT_TEMPLATE;

		if ( $post instanceof \WP_Post ) {
			$post_template = get_post_meta( $post->ID, '_wpdfv_reader_template', true );
			$post_template = is_string( $post_template ) ? sanitize_key( $post_template ) : '';

			if ( '' !== $post_template && isset( self::get_registered_templates()[ $post_template ] ) ) {
				$slug = sanitize_key( $post_template );
			}
		}

		return self::sanitize_template_slug( $slug );
	}

	/**
	 * Sanitize and validate a modal template slug.
	 *
	 * @since 1.7.0
	 *
	 * @param string $slug Template slug.
	 *
	 * @return string
	 */
	public static function sanitize_template_slug( $slug ) {
		$slug      = sanitize_key( $slug );
		$templates = self::get_registered_templates();

		return isset( $templates[ $slug ] ) ? $slug : self::DEFAULT_TEMPLATE;
	}

	/**
	 * Get registered modal templates.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_registered_templates() {
		if ( null !== self::$registered_templates_cache ) {
			return self::$registered_templates_cache;
		}

		$templates = [
			self::DEFAULT_TEMPLATE => [
				'label'       => __( 'Default Reader Mode layout', 'wp-distraction-free-view' ),
				'description' => __( 'Displays the current post content inside the frontend Reader Mode view.', 'wp-distraction-free-view' ),
				'category'    => self::PATTERN_CATEGORY,
				'content'     => self::get_default_template_content(),
				'preview'     => null,
			],
		];

		/**
		 * Filter available block-based modal templates.
		 *
		 * Block themes can use this to register additional modal layouts with
		 * block markup and expose them in the plugin settings.
		 *
		 * @since 1.7.0
		 *
		 * @param array $templates Modal template definitions keyed by slug.
		 */
		$templates = apply_filters( 'wpdfv_modal_templates', $templates );

		self::$registered_templates_cache = self::normalize_templates( $templates );

		return self::$registered_templates_cache;
	}

	/**
	 * Get default block markup for the modal template.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	protected static function get_default_template_content() {
		return '<!-- wp:group {"tagName":"article","className":"wpdfv-modal-template wpdfv-modal-template--default","layout":{"type":"default"}} -->' . "\n" .
			'<!-- wp:post-content /-->' . "\n" .
			'<!-- /wp:group -->';
	}

	/**
	 * Normalize templates registered by the plugin and third-party code.
	 *
	 * @since 1.7.0
	 *
	 * @param array $templates Raw templates.
	 *
	 * @return array
	 */
	protected static function normalize_templates( $templates ) {
		$normalized = [];

		if ( ! is_array( $templates ) ) {
			$templates = [];
		}

		foreach ( $templates as $slug => $template ) {
			$slug = sanitize_key( $slug );

			if ( ! $slug || ! is_array( $template ) || ! isset( $template['content'] ) || ! is_string( $template['content'] ) || '' === trim( $template['content'] ) ) {
				continue;
			}

			$preview  = null;
			$label    = isset( $template['label'] ) ? sanitize_text_field( $template['label'] ) : '';
			$category = isset( $template['category'] ) ? sanitize_key( $template['category'] ) : '';

			if ( '' === $label ) {
				$label = self::DEFAULT_TEMPLATE === $slug
					? __( 'Default Reader Mode layout', 'wp-distraction-free-view' )
					: $slug;
			}

			if ( '' === $category ) {
				$category = self::PATTERN_CATEGORY;
			}

			if ( isset( $template['preview'] ) && is_array( $template['preview'] ) && ! empty( $template['preview']['image'] ) ) {
				$preview_url = esc_url_raw( $template['preview']['image'] );
				$preview_alt = isset( $template['preview']['alt'] ) ? (string) $template['preview']['alt'] : '';
				$preview_alt = preg_replace( '#<(script|style|noscript)\b[^>]*>.*?</\1\s*>#is', '', $preview_alt );

				if ( $preview_url ) {
					$preview = [
						'image' => $preview_url,
						'alt'   => sanitize_text_field( $preview_alt ),
					];
				}
			}

			$normalized[ $slug ] = [
				'label'       => $label,
				'description' => ! empty( $template['description'] ) ? sanitize_text_field( $template['description'] ) : '',
				'category'    => $category,
				'content'     => (string) $template['content'],
				'preview'     => $preview,
			];
		}

		if ( empty( $normalized[ self::DEFAULT_TEMPLATE ] ) ) {
			$normalized[ self::DEFAULT_TEMPLATE ] = [
				'label'       => __( 'Default Reader Mode layout', 'wp-distraction-free-view' ),
				'description' => __( 'Displays the current post content inside the frontend Reader Mode view.', 'wp-distraction-free-view' ),
				'category'    => self::PATTERN_CATEGORY,
				'content'     => self::get_default_template_content(),
				'preview'     => null,
			];
		}

		return $normalized;
	}

	/**
	 * Check if the modal pattern category is already registered.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	protected function is_pattern_category_registered( $category = self::PATTERN_CATEGORY ) {
		if ( ! class_exists( '\WP_Block_Pattern_Categories_Registry' ) ) {
			return false;
		}

		return \WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $category );
	}

	/**
	 * Check if a pattern is already registered.
	 *
	 * @since 1.7.0
	 *
	 * @param string $pattern_name Pattern name.
	 *
	 * @return bool
	 */
	protected function is_pattern_registered( $pattern_name ) {
		if ( ! class_exists( '\WP_Block_Patterns_Registry' ) ) {
			return false;
		}

		return \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern_name );
	}
}
