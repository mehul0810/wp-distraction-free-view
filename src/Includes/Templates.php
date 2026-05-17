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
		$options = [];

		foreach ( self::get_registered_templates() as $slug => $template ) {
			$options[] = [
				'label'       => $template['label'],
				'value'       => $slug,
				'description' => $template['description'],
			];
		}

		return $options;
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
		$template_slug = self::get_selected_template_slug();
		$template      = $templates[ $template_slug ] ?? $templates[ self::DEFAULT_TEMPLATE ];
		$previous_post = $GLOBALS['post'] ?? null;

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for core post blocks in the modal template.
		setup_postdata( $post );

		try {
			$content = Helpers::without_button_injection(
				static function () use ( $template ) {
					return do_blocks( $template['content'] );
				}
			);
		} finally {
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
	public static function get_selected_template_slug() {
		$settings = Helpers::get_settings();
		$slug     = isset( $settings['modal_template'] ) ? sanitize_key( $settings['modal_template'] ) : self::DEFAULT_TEMPLATE;

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
		$templates = [
			self::DEFAULT_TEMPLATE => [
				'label'       => __( 'Default Reader Mode layout', 'wp-distraction-free-view' ),
				'description' => __( 'Displays the current post content inside the frontend Reader Mode view.', 'wp-distraction-free-view' ),
				'category'    => self::PATTERN_CATEGORY,
				'content'     => self::get_default_template_content(),
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

		return self::normalize_templates( $templates );
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

			if ( ! $slug || ! is_array( $template ) || empty( $template['content'] ) ) {
				continue;
			}

			$normalized[ $slug ] = [
				'label'       => ! empty( $template['label'] ) ? sanitize_text_field( $template['label'] ) : $slug,
				'description' => ! empty( $template['description'] ) ? sanitize_text_field( $template['description'] ) : '',
				'category'    => ! empty( $template['category'] ) ? sanitize_key( $template['category'] ) : self::PATTERN_CATEGORY,
				'content'     => (string) $template['content'],
			];
		}

		if ( empty( $normalized[ self::DEFAULT_TEMPLATE ] ) ) {
			$normalized[ self::DEFAULT_TEMPLATE ] = [
				'label'       => __( 'Default Reader Mode layout', 'wp-distraction-free-view' ),
				'description' => __( 'Displays the current post content inside the frontend Reader Mode view.', 'wp-distraction-free-view' ),
				'category'    => self::PATTERN_CATEGORY,
				'content'     => self::get_default_template_content(),
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
	protected function is_pattern_category_registered() {
		if ( ! class_exists( '\WP_Block_Pattern_Categories_Registry' ) ) {
			return false;
		}

		return \WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( self::PATTERN_CATEGORY );
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
