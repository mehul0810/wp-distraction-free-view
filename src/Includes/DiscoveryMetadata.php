<?php
/**
 * WP Distraction Free View | opt-in discovery metadata.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optionally exposes discoverable links and JSON-LD for public Reader content.
 */
class DiscoveryMetadata {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'render' ] );
	}

	/**
	 * Render opt-in metadata for the current publicly readable post.
	 *
	 * @return void
	 */
	public function render() {
		static $rendered = false;

		if ( $rendered || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();

		if (
			! $post instanceof \WP_Post ||
			! Reader::is_post_enabled_for_post( $post ) ||
			post_password_required( $post ) ||
			! Content::is_public_post( $post )
		) {
			return;
		}

		/**
		 * Whether to expose Reader Mode discovery metadata on this public post.
		 *
		 * Metadata is off by default. Returning true publishes an alternate
		 * structured-content link and Article JSON-LD for this post.
		 *
		 * @since 1.9.0
		 *
		 * @param bool     $enabled Whether to emit metadata.
		 * @param \WP_Post $post    Public post.
		 */
		$settings = Reader::get_settings();
		$enabled  = ! empty( $settings['discovery_metadata_enabled'] );

		if ( ! apply_filters( 'wpdfv_discovery_metadata_enabled', $enabled, $post ) ) {
			return;
		}

		$data     = Content::get_structured_data( $post );
		$metadata = [
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'mainEntityOfPage' => $data['canonicalUrl'],
			'headline'         => $data['title'],
			'description'      => $data['excerpt'],
			'datePublished'    => $data['publishedAt'],
			'dateModified'     => $data['modifiedAt'],
			'inLanguage'       => $data['language'],
		];

		if ( $data['author'] ) {
			$metadata['author'] = [
				'@type' => 'Person',
				'name'  => $data['author'],
			];
		}

		if ( $data['featuredImage'] ) {
			$metadata['image'] = $data['featuredImage']['url'];
		}

		/**
		 * Filter generated Reader Mode discovery metadata.
		 *
		 * @since 1.9.0
		 *
		 * @param array    $metadata JSON-LD object.
		 * @param array    $data     Structured Reader Mode content.
		 * @param \WP_Post $post     Public post.
		 */
		$metadata = apply_filters( 'wpdfv_discovery_metadata', $metadata, $data, $post );

		if ( ! is_array( $metadata ) ) {
			return;
		}

		$endpoint = rest_url( WPDFV_REST_NAMESPACE . '/structured-content/' . $post->ID );
		$json     = wp_json_encode( $metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		if ( ! is_string( $json ) ) {
			return;
		}

		printf(
			'<link rel="alternate" type="application/json" href="%1$s" />' . "\n" . '<script type="application/ld+json">%2$s</script>' . "\n",
			esc_url( $endpoint ),
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* flags prevent markup injection.
		);

		$rendered = true;
	}
}
