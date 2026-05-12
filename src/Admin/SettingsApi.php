<?php
/**
 * WP Distraction Free View - Admin Settings API.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Admin;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPDFV\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page and REST API controller.
 *
 * @since 1.0.0
 */
class SettingsApi {

	/**
	 * Base prefix for settings.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $prefix = 'wpdfv';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Render settings page shell.
	 *
	 * @since 1.6.0
	 *
	 * @return void
	 */
	public function settings_page() {
		?>
		<div class="wrap wpdfv-settings-page">
			<div id="wpdfv-settings-app"></div>
		</div>
		<?php
	}

	/**
	 * Register REST routes used by the settings app.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			WPDFV_REST_NAMESPACE,
			'/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings_response' ],
					'permission_callback' => [ $this, 'can_manage_settings' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings_response' ],
					'permission_callback' => [ $this, 'can_manage_settings' ],
				],
			]
		);
	}

	/**
	 * Check whether the current user can manage settings.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Return settings data for the admin app.
	 *
	 * @since 2.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings_response() {
		return rest_ensure_response(
			[
				'settings'           => $this->get_prepared_settings(),
				'defaults'           => $this->get_default_settings(),
				'postTypes'          => array_values( $this->get_public_post_types() ),
				'displayLocations'   => $this->get_display_locations(),
				'recommendedPlugins' => $this->get_recommended_plugins(),
				'restNamespace'      => WPDFV_REST_NAMESPACE,
				'minimumWordPress'   => '6.0',
				'minimumPhp'         => '8.2',
				'pluginVersion'      => WPDFV_VERSION,
			]
		);
	}

	/**
	 * Update plugin settings.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_settings_response( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( ! is_array( $data ) ) {
			$data = $request->get_body_params();
		}

		$settings = $this->sanitize_settings_data( $data );

		update_option( $this->get_settings_key(), $settings, false );

		return $this->get_settings_response();
	}

	/**
	 * Get the settings option key.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function get_settings_key() {
		return "{$this->prefix}_settings";
	}

	/**
	 * Get default settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return [
			'where_to_display' => [ 'post', 'page' ],
			'display_location' => 'after_content',
			'button_text'      => Helpers::get_default_button_text(),
		];
	}

	/**
	 * Get settings merged with defaults.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_prepared_settings() {
		$settings = get_option( $this->get_settings_key(), [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return array_merge( $this->get_default_settings(), $settings );
	}

	/**
	 * Get public post types available for read mode.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_public_post_types() {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$options    = [];

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = [
				'slug'  => $post_type->name,
				'label' => $post_type->labels->singular_name,
			];
		}

		return $options;
	}

	/**
	 * Get allowed display locations.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_display_locations() {
		return [
			[
				'label' => esc_html__( 'Disable automatic buttons', 'wp-distraction-free-view' ),
				'value' => 'disable',
			],
			[
				'label' => esc_html__( 'Before content', 'wp-distraction-free-view' ),
				'value' => 'before_content',
			],
			[
				'label' => esc_html__( 'After content', 'wp-distraction-free-view' ),
				'value' => 'after_content',
			],
		];
	}

	/**
	 * Get recommended plugin cards.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_recommended_plugins() {
		return [
			[
				'label'       => esc_html__( 'Perform', 'wp-distraction-free-view' ),
				'description' => esc_html__( 'Optimize WordPress performance with focused caching and runtime improvements.', 'wp-distraction-free-view' ),
				'url'         => 'https://wordpress.org/plugins/perform',
			],
			[
				'label'       => esc_html__( 'Klaive - Integrates Klaviyo + GiveWP', 'wp-distraction-free-view' ),
				'description' => esc_html__( 'Connect GiveWP donation activity with Klaviyo email audiences.', 'wp-distraction-free-view' ),
				'url'         => 'https://wordpress.org/plugins/klaive',
			],
		];
	}

	/**
	 * Sanitize settings data.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data Settings data to sanitize.
	 *
	 * @return array
	 */
	protected function sanitize_settings_data( $data ) {
		$defaults          = $this->get_default_settings();
		$public_post_types = array_keys( $this->get_public_post_types() );
		$display_locations = wp_list_pluck( $this->get_display_locations(), 'value' );
		$where_to_display  = isset( $data['where_to_display'] ) && is_array( $data['where_to_display'] ) ? $data['where_to_display'] : $defaults['where_to_display'];
		$display_location  = isset( $data['display_location'] ) ? sanitize_key( $data['display_location'] ) : $defaults['display_location'];
		$button_text       = isset( $data['button_text'] ) ? sanitize_text_field( $data['button_text'] ) : $defaults['button_text'];
		$where_to_display  = array_values( array_intersect( array_map( 'sanitize_key', $where_to_display ), $public_post_types ) );
		$display_location  = in_array( $display_location, $display_locations, true ) ? $display_location : $defaults['display_location'];
		$button_text       = '' !== $button_text ? $button_text : $defaults['button_text'];

		return [
			'where_to_display' => $where_to_display,
			'display_location' => $display_location,
			'button_text'      => $button_text,
		];
	}
}
