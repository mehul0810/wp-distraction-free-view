<?php
/**
 * WP Distraction Free View - [wpdfv] shortcode.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes\Shortcodes;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPDFV\Includes\Actions;
use WPDFV\Includes\Helpers;
use WPDFV\Includes\Reader;
use WPDFV\Includes\Templates;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Main {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_shortcode( 'wpdfv', [ $this, 'render_shortcode' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Display shortcode content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts List of shortcode attributes.
	 *
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'post_id' => 0,
			],
			$atts,
			'wpdfv'
		);

		$post_id = absint( $atts['post_id'] );

		if ( ! $post_id ) {
			global $post;
			$post_id = $post instanceof \WP_Post ? $post->ID : 0;
		}

		$post_type = get_post_type( $post_id );

		if ( ! $post_type || ! Reader::is_post_type_enabled( $post_type ) ) {
			return '';
		}

		Actions::enqueue_frontend_assets();

		return Helpers::display_read_mode_button( $post_id );
	}

	/**
	 * Register REST routes for reader content.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			WPDFV_REST_NAMESPACE,
			'/content/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_content_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'description'       => __( 'Post ID to render in Reader Mode.', 'wp-distraction-free-view' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Get reader content for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_content_response( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$post    = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return new WP_Error(
				'wpdfv_post_not_found',
				__( 'Post not found.', 'wp-distraction-free-view' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $this->can_read_post( $post ) ) {
			return new WP_Error(
				'wpdfv_post_forbidden',
				__( 'This content is not available in Reader Mode.', 'wp-distraction-free-view' ),
				[ 'status' => 403 ]
			);
		}

		$prepared = Reader::prepare_rendered_content( Templates::render_modal_content( $post ), $post );
		$minutes  = Reader::calculate_reading_time( $post->post_content );

		return rest_ensure_response(
			[
				'id'          => $post->ID,
				'title'       => get_the_title( $post ),
				'permalink'   => get_permalink( $post ),
				'content'     => $prepared['content'],
				'scripts'     => $prepared['scripts'],
				'readingTime' => [
					'minutes' => $minutes,
					'label'   => Reader::format_reading_time_label( $minutes ),
				],
			]
		);
	}

	/**
	 * Determine whether a post can be read through the public reader endpoint.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return bool
	 */
	protected function can_read_post( \WP_Post $post ) {
		if ( ! Reader::is_post_type_enabled( $post->post_type ) ) {
			return false;
		}

		if ( post_password_required( $post ) ) {
			return false;
		}

		if ( current_user_can( 'read_post', $post->ID ) ) {
			return true;
		}

		return 'publish' === get_post_status( $post ) && is_post_publicly_viewable( $post );
	}
}
