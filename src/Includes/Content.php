<?php
/**
 * WP Distraction Free View | public content contracts.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared access and structured-content representation for public integrations.
 */
class Content {

	/**
	 * Check whether a post may be read by the current user through Reader Mode.
	 *
	 * @param \WP_Post $post Post to check.
	 *
	 * @return bool
	 */
	public static function can_read_post( \WP_Post $post ) {
		if ( ! Reader::is_post_enabled_for_post( $post ) || post_password_required( $post ) ) {
			return false;
		}

		if ( current_user_can( 'read_post', $post->ID ) ) {
			return true;
		}

		return self::is_public_post( $post );
	}

	/**
	 * Check whether a post is available to anonymous public readers.
	 *
	 * @param \WP_Post $post Post to check.
	 *
	 * @return bool
	 */
	public static function is_public_post( \WP_Post $post ) {
		return 'publish' === get_post_status( $post ) && is_post_publicly_viewable( $post );
	}

	/**
	 * Build the structured, sanitized representation of a post.
	 *
	 * Caller must enforce access before invoking this method.
	 *
	 * @param \WP_Post $post Public post being represented.
	 *
	 * @return array
	 */
	public static function get_structured_data( \WP_Post $post ) {
		$prepared   = Reader::prepare_rendered_content( Templates::render_modal_content( $post ), $post );
		$html       = $prepared['content'];
		$text       = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( $html, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ) );
		$minutes    = Reader::calculate_reading_time( $text );
		$post_type  = get_post_type_object( $post->post_type );
		$author     = self::get_public_author_name( $post );
		$image_id   = get_post_thumbnail_id( $post );
		$image_data = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;
		$data       = [
			'id'            => $post->ID,
			'canonicalUrl'  => get_permalink( $post ),
			'title'         => get_the_title( $post ),
			'excerpt'       => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'text'          => $text,
			'html'          => $html,
			'readingTime'   => [
				'minutes' => $minutes,
				'label'   => Reader::format_reading_time_label( $minutes ),
			],
			'language'      => get_bloginfo( 'language' ),
			'author'        => $author,
			'publishedAt'   => get_post_time( DATE_W3C, true, $post ),
			'modifiedAt'    => get_post_modified_time( DATE_W3C, true, $post ),
			'postType'      => $post_type ? $post_type->name : $post->post_type,
			'featuredImage' => $image_data ? [
				'url' => $image_data[0],
				'alt' => (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			] : null,
		];

		/**
		 * Filter structured Reader Mode data.
		 *
		 * @since 1.9.0
		 *
		 * @param array    $data Structured content.
		 * @param \WP_Post $post Public post being returned.
		 */
		$data = apply_filters( 'wpdfv_structured_reader_content', $data, $post );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Get an author's public display name when available.
	 *
	 * @param \WP_Post $post Post to inspect.
	 *
	 * @return string|null
	 */
	protected static function get_public_author_name( \WP_Post $post ) {
		$author = get_userdata( (int) $post->post_author );

		if ( ! $author instanceof \WP_User || '' === trim( $author->display_name ) ) {
			return null;
		}

		return (string) $author->display_name;
	}
}
