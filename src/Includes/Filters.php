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

		$new_content    = '';
		$display_btn_at = wpdfv_display_read_mode_btn_at();
		$button_html    = wpdfv_display_read_mode_button( $post->ID );

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
