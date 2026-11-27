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
		$prepared = Reader::prepare_rendered_content( Templates::render_modal_content( $post ), $post );
		$html     = $prepared['content'];
		$text     = self::get_visible_text( $html );
		$minutes  = Reader::calculate_plain_text_reading_time( $text );
		$data     = self::get_article_metadata( $post, true );

		$data['text']        = $text;
		$data['html']        = $html;
		$data['readingTime'] = [
			'minutes' => $minutes,
			'label'   => Reader::format_reading_time_label( $minutes ),
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
	 * Extract plain text while preserving inline words, block boundaries and escaped code.
	 *
	 * @param string $html Sanitized Reader HTML.
	 * @return string
	 */
	private static function get_visible_text( $html ) {
		$parts = wp_html_split( $html );
		foreach ( $parts as $index => $part ) {
			if ( preg_match( '~^</?(?:address|article|aside|blockquote|br|caption|dd|details|div|dl|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|hr|li|main|nav|ol|p|pre|section|summary|table|td|th|tr|ul)\b~i', $part ) ) {
				$parts[ $index ] = ' ' . $part;
			}
		}

		$text = wp_strip_all_tags( implode( '', $parts ) );
		$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Build the post metadata shared by discovery and the structured representation.
	 *
	 * @param \WP_Post $post             Readable post.
	 * @param bool     $generate_excerpt Generate an excerpt from content when none was written.
	 * @return array
	 */
	public static function get_article_metadata( \WP_Post $post, $generate_excerpt ) {
		$post_type  = get_post_type_object( $post->post_type );
		$author     = self::get_public_author_name( $post );
		$image_id   = get_post_thumbnail_id( $post );
		$image_data = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;

		return [
			'id'            => $post->ID,
			'canonicalUrl'  => get_permalink( $post ),
			'title'         => get_the_title( $post ),
			'excerpt'       => $generate_excerpt || '' !== trim( $post->post_excerpt ) ? wp_strip_all_tags( get_the_excerpt( $post ) ) : '',
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
