<?php
/**
 * WP Distraction Free View | WordPress Abilities API integration.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers read-only Reader Mode content access for WordPress 6.9+.
 */
class Abilities {

	/**
	 * Ability category slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'wpdfv-reader-mode';

	/**
	 * Ability name.
	 *
	 * @var string
	 */
	const ABILITY = 'wp-distraction-free-view/get-reader-content';

	/**
	 * Register API hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', [ $this, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ $this, 'register_ability' ] );
	}

	/**
	 * Register the Reader Mode ability category when supported.
	 *
	 * @return void
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( self::CATEGORY ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			[
				'label'       => __( 'Reader Mode', 'wp-distraction-free-view' ),
				'description' => __( 'Read-only access to public content available in Reader Mode.', 'wp-distraction-free-view' ),
			]
		);
	}

	/**
	 * Register the public read-only content ability when supported.
	 *
	 * @return void
	 */
	public function register_ability() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		if ( function_exists( 'wp_has_ability' ) && wp_has_ability( self::ABILITY ) ) {
			return;
		}

		wp_register_ability(
			self::ABILITY,
			[
				'label'               => __( 'Get Reader Mode content', 'wp-distraction-free-view' ),
				'description'         => __( 'Returns structured Reader Mode data for publicly readable content.', 'wp-distraction-free-view' ),
				'category'            => self::CATEGORY,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'post_id' => [
							'type'        => 'integer',
							'description' => __( 'ID of the public post to read.', 'wp-distraction-free-view' ),
						],
					],
					'required'             => [ 'post_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'                 => 'object',
					'properties'           => [
						'id'            => [ 'type' => 'integer' ],
						'canonicalUrl'  => [ 'type' => 'string' ],
						'title'         => [ 'type' => 'string' ],
						'excerpt'       => [ 'type' => 'string' ],
						'text'          => [ 'type' => 'string' ],
						'html'          => [ 'type' => 'string' ],
						'language'      => [ 'type' => 'string' ],
						'publishedAt'   => [ 'type' => 'string' ],
						'modifiedAt'    => [ 'type' => 'string' ],
						'postType'      => [ 'type' => 'string' ],
						'author'        => [ 'type' => [ 'string', 'null' ] ],
						'featuredImage' => [ 'type' => [ 'object', 'null' ] ],
						'readingTime'   => [ 'type' => 'object' ],
					],
					'additionalProperties' => true,
				],
				'execute_callback'    => [ $this, 'get_reader_content' ],
				'permission_callback' => [ $this, 'can_read_content' ],
				'meta'                => [
					'public'       => true,
					'show_in_rest' => true,
					'annotations'  => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
				],
			]
		);
	}

	/**
	 * Check whether the requested post is available to public readers.
	 *
	 * @param array $input Ability input.
	 *
	 * @return bool
	 */
	public function can_read_content( $input ) {
		$post = $this->get_public_post( $input );

		return $post instanceof \WP_Post;
	}

	/**
	 * Return structured Reader Mode content.
	 *
	 * @param array $input Ability input.
	 *
	 * @return array|\WP_Error
	 */
	public function get_reader_content( $input ) {
		$post = $this->get_public_post( $input );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error(
				'wpdfv_post_forbidden',
				__( 'This content is not available in Reader Mode.', 'wp-distraction-free-view' )
			);
		}

		return Content::get_structured_data( $post );
	}

	/**
	 * Resolve and validate a publicly readable post from ability input.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return \WP_Post|null
	 */
	protected function get_public_post( $input ) {
		if ( ! is_array( $input ) || empty( $input['post_id'] ) ) {
			return null;
		}

		$post = get_post( absint( $input['post_id'] ) );

		if (
			! $post instanceof \WP_Post ||
			! Reader::is_post_enabled_for_post( $post ) ||
			post_password_required( $post ) ||
			! Content::is_public_post( $post )
		) {
			return null;
		}

		return $post;
	}
}
