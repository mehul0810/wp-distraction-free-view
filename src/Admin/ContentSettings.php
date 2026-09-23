<?php
/**
 * WP Distraction Free View | per-content Reader Mode settings.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Admin;

use WPDFV\Includes\Reader;
use WPDFV\Includes\Templates;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds per-post availability and template overrides to public content editors.
 */
class ContentSettings {

	/**
	 * Per-post template meta key.
	 *
	 * @var string
	 */
	const TEMPLATE_META = '_wpdfv_reader_template';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'wpdfv_content_settings_nonce';

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ] );
	}

	/**
	 * Register the public, editor-authorized post meta fields.
	 *
	 * @return void
	 */
	public function register_meta() {
		if ( ! function_exists( 'register_post_meta' ) ) {
			return;
		}

		foreach ( $this->get_public_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				Reader::POST_AVAILABILITY_META,
				[
					'type'              => 'string',
					'single'            => true,
					'default'           => 'inherit',
					'show_in_rest'      => true,
					'sanitize_callback' => [ $this, 'sanitize_availability' ],
					'auth_callback'     => [ $this, 'can_edit_post_meta' ],
				]
			);
			register_post_meta(
				$post_type,
				self::TEMPLATE_META,
				[
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => [ $this, 'sanitize_template' ],
					'auth_callback'     => [ $this, 'can_edit_post_meta' ],
				]
			);
		}
	}

	/**
	 * Add the settings panel to public post editors.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		$post_types = $this->get_public_post_types();

		if ( empty( $post_types ) ) {
			return;
		}

		add_meta_box(
			'wpdfv-reader-settings',
			__( 'Reader Mode', 'wp-distraction-free-view' ),
			[ $this, 'render_meta_box' ],
			$post_types,
			'side',
			'default'
		);
	}

	/**
	 * Render availability and template controls.
	 *
	 * @param \WP_Post $post Current post.
	 *
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ) {
		wp_nonce_field( 'wpdfv_save_content_settings', self::NONCE_NAME );
		$availability = get_post_meta( $post->ID, Reader::POST_AVAILABILITY_META, true );
		$template     = get_post_meta( $post->ID, self::TEMPLATE_META, true );

		if ( ! in_array( $availability, [ 'enabled', 'disabled' ], true ) ) {
			$availability = 'inherit';
		}

		echo '<p><label for="wpdfv-reader-availability">' . esc_html__( 'Availability', 'wp-distraction-free-view' ) . '</label></p>';
		echo '<select class="widefat" id="wpdfv-reader-availability" name="wpdfv_reader_availability">';
		foreach (
			[
				'inherit'  => __( 'Use global settings', 'wp-distraction-free-view' ),
				'enabled'  => __( 'Enabled for this content', 'wp-distraction-free-view' ),
				'disabled' => __( 'Disabled for this content', 'wp-distraction-free-view' ),
			] as $value => $label
		) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $availability, $value, false ), esc_html( $label ) );
		}
		echo '</select>';

		echo '<p><label for="wpdfv-reader-template">' . esc_html__( 'Template', 'wp-distraction-free-view' ) . '</label></p>';
		echo '<select class="widefat" id="wpdfv-reader-template" name="wpdfv_reader_template">';
		printf( '<option value="">%s</option>', esc_html__( 'Use global template', 'wp-distraction-free-view' ) );
		foreach ( Templates::get_template_options() as $option ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $option['value'] ),
				selected( $template, $option['value'], false ),
				esc_html( $option['label'] )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'These settings apply to this item in the Reader Mode button, blocks, and content endpoint.', 'wp-distraction-free-view' ) . '</p>';
	}

	/**
	 * Persist submitted per-content settings.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_post( $post_id ) {
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), 'wpdfv_save_content_settings' ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			wp_is_post_revision( $post_id ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		$availability = isset( $_POST['wpdfv_reader_availability'] ) ? sanitize_key( wp_unslash( $_POST['wpdfv_reader_availability'] ) ) : 'inherit';
		$template     = isset( $_POST['wpdfv_reader_template'] ) ? sanitize_key( wp_unslash( $_POST['wpdfv_reader_template'] ) ) : '';

		$availability = $this->sanitize_availability( $availability );
		$template     = $this->sanitize_template( $template );

		if ( 'inherit' === $availability ) {
			delete_post_meta( $post_id, Reader::POST_AVAILABILITY_META );
		} else {
			update_post_meta( $post_id, Reader::POST_AVAILABILITY_META, $availability );
		}

		if ( '' === $template ) {
			delete_post_meta( $post_id, self::TEMPLATE_META );
		} else {
			update_post_meta( $post_id, self::TEMPLATE_META, $template );
		}
	}

	/**
	 * Sanitize availability metadata.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public function sanitize_availability( $value ) {
		$value = sanitize_key( is_scalar( $value ) ? (string) $value : '' );

		return in_array( $value, [ 'inherit', 'enabled', 'disabled' ], true ) ? $value : 'inherit';
	}

	/**
	 * Sanitize a template override.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public function sanitize_template( $value ) {
		$value = sanitize_key( is_scalar( $value ) ? (string) $value : '' );

		if ( '' === $value ) {
			return '';
		}

		$templates = Templates::get_registered_templates();

		return isset( $templates[ $value ] ) ? $value : '';
	}

	/**
	 * Check permission for REST access to registered post metadata.
	 *
	 * @param bool   $allowed Whether core allows access.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id Post ID.
	 *
	 * @return bool
	 */
	public function can_edit_post_meta( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get public post types with an editor interface.
	 *
	 * @return array
	 */
	protected function get_public_post_types() {
		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			]
		);

		return array_values( array_diff( $post_types, [ 'attachment' ] ) );
	}
}
