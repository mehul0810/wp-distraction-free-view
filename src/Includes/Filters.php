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

		// Get data about where to display.
		$where_to_display = Helpers::where_to_display();

		// Bailout, if not to show on specific post type.
		if ( ! in_array( $post->post_type, $where_to_display, true ) ) {
			return $content;
		}

		$new_content    = '';
		$display_btn_at = Helpers::display_location();
		$button_html    = Helpers::display_read_mode_button( $post->ID );

		// Bailout, if the display button at setting is disabled.
		if ( 'disable' === $display_btn_at ) {
			return $content;
		} elseif ( 'before_content' === $display_btn_at ) {
			$new_content .= $button_html;
			$new_content .= $content;
		} elseif ( 'after_content' === $display_btn_at ) {
			$new_content .= $content;
			$new_content .= $button_html;
		}

		return $new_content;
	}
}
