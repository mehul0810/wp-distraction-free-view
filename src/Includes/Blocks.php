<?php
/**
 * WP Distraction Free View | Blocks.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blocks {
	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_category' ] );
	}

	/**
	 * Register plugin blocks.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_blocks() {
		register_block_type(
			WPDFV_PLUGIN_DIR . 'blocks/reader-button',
			[
				'render_callback' => [ $this, 'render_reader_button' ],
			]
		);
	}

	/**
	 * Register a block category for WP Distraction Free View blocks.
	 *
	 * @since 2.0.0
	 *
	 * @param array $categories Block categories.
	 *
	 * @return array
	 */
	public function register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'wpdfv' === $category['slug'] ) {
				return $categories;
			}
		}

		array_unshift(
			$categories,
			[
				'slug'  => 'wpdfv',
				'title' => __( 'WP Distraction Free View', 'wp-distraction-free-view' ),
			]
		);

		return $categories;
	}

	/**
	 * Render the reader button block.
	 *
	 * @since 2.0.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 *
	 * @return string
	 */
	public function render_reader_button( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		$button_text = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : '';

		return Helpers::display_read_mode_button(
			$post_id,
			$button_text,
			get_block_wrapper_attributes( [ 'class' => 'wpdfv-fullscreen-container' ] )
		);
	}
}
