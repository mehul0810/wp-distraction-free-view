<?php
/**
 * WP Distraction Free View | Frontend Filters.
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

class Filters {
	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'the_content', [ $this, 'filter_content' ] );
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
	}

	/**
	 * This function is used to filter the content to display distraction free view button.
	 *
	 * @param mixed $content Content.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function filter_content( $content ) {
		global $post;

		if ( Helpers::is_button_injection_suspended() || ! $post instanceof \WP_Post ) {
			return $content;
		}

		if ( ! Helpers::is_automatic_button_enabled() ) {
			return $content;
		}

		// Bailout, if not to show on specific post type.
		if ( ! Reader::is_post_type_enabled( $post->post_type ) ) {
			return $content;
		}

		$new_content    = '';
		$display_btn_at = Helpers::display_location();
		$button_html    = Helpers::display_read_mode_button( $post->ID );

		// Bailout, if the display button at setting is disabled.
		if ( 'manual_only' === $display_btn_at || 'floating' === $display_btn_at ) {
			return $content;
		} elseif ( 'before_content' === $display_btn_at ) {
			$new_content .= $button_html;
			$new_content .= $content;
		} elseif ( 'after_content' === $display_btn_at ) {
			$new_content .= $content;
			$new_content .= $button_html;
		} else {
			return $content;
		}

		return $new_content;
	}

	/**
	 * Add body classes when Reader Mode is requested through the URL.
	 *
	 * @since 1.7.0
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array
	 */
	public function add_body_classes( $classes ) {
		if ( Reader::is_reader_mode_request() ) {
			$classes[] = 'wpdfv-reader-mode-requested';
		}

		return $classes;
	}
}
