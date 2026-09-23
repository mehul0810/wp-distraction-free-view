<?php
/**
 * WP Distraction Free View - Admin Settings API.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Admin;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPDFV\Includes\Reader;
use WPDFV\Includes\Templates;

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
	 * Public post type options cache for the current request.
	 *
	 * @since 1.8.0
	 *
	 * @var array|null
	 */
	protected $public_post_types_cache = null;

	/**
	 * More Plugins catalog service.
	 *
	 * @var MorePluginsCatalog
	 */
	protected $plugin_catalog;

	/**
	 * Companion plugin action service.
	 *
	 * @var PluginActions
	 */
	protected $plugin_actions;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_catalog = new MorePluginsCatalog();
		$this->plugin_actions = new PluginActions( $this->plugin_catalog, [ $this, 'get_settings_response' ] );
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
	 * @since 1.7.0
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

		register_rest_route(
			WPDFV_REST_NAMESPACE,
			'/plugins/(?P<slug>[a-z0-9-]+)/(?P<action>install|activate)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_plugin_action' ],
				'permission_callback' => [ $this, 'can_manage_plugin_actions' ],
				'args'                => [
					'slug'   => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
					'action' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * Check whether the current user can manage settings.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check whether the current user can install or activate plugins.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function can_manage_plugin_actions() {
		return current_user_can( 'install_plugins' ) || current_user_can( 'activate_plugins' );
	}

	/**
	 * Return settings data for the admin app.
	 *
	 * @since 1.7.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings_response() {
		$more_plugins = $this->get_more_plugins();
		$can_edit_css = current_user_can( Reader::get_custom_css_capability() );
		$settings     = $this->get_prepared_settings();

		if ( ! $can_edit_css ) {
			$settings['custom_css'] = '';
		}

		return rest_ensure_response(
			[
				'settings'           => $settings,
				'defaults'           => $this->get_default_settings(),
				'postTypes'          => array_values( $this->get_public_post_types() ),
				'displayLocations'   => $this->get_display_locations(),
				'readerThemes'       => Reader::get_reader_theme_options(),
				'contentWidths'      => Reader::get_content_width_options(),
				'fontSizes'          => Reader::get_font_size_options(),
				'modalTemplates'     => Templates::get_template_options(),
				'morePlugins'        => $more_plugins,
				'recommendedPlugins' => array_merge( $more_plugins['free'], $more_plugins['paid'] ),
				'restNamespace'      => WPDFV_REST_NAMESPACE,
				'minimumWordPress'   => '6.0',
				'minimumPhp'         => '8.2',
				'pluginVersion'      => WPDFV_VERSION,
				'brandIconUrl'       => WPDFV_PLUGIN_URL . 'assets/dist/images/wpdfv-icon.png',
				'canEditCustomCss'   => $can_edit_css,
			]
		);
	}

	/**
	 * Handle companion plugin install or activate actions.
	 *
	 * @since 1.7.0
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_plugin_action( WP_REST_Request $request ) {
		return $this->plugin_actions->handle( $request );
	}

	/**
	 * Update plugin settings.
	 *
	 * @since 1.7.0
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

		$settings          = $this->sanitize_settings_data( $data );
		$existing_settings = $this->get_prepared_settings();

		if ( ! current_user_can( Reader::get_custom_css_capability() ) ) {
			$settings['custom_css'] = isset( $existing_settings['custom_css'] ) ? $existing_settings['custom_css'] : '';
		}

		update_option( $this->get_settings_key(), $settings, false );
		$this->invalidate_settings_request_cache();

		return $this->get_settings_response();
	}

	/**
	 * Get the settings option key.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	protected function get_settings_key() {
		return "{$this->prefix}_settings";
	}

	/**
	 * Get default settings.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return Reader::get_default_settings();
	}

	/**
	 * Get settings merged with defaults.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_prepared_settings() {
		$settings = get_option( $this->get_settings_key(), [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return Reader::sanitize_settings_data( array_merge( $this->get_default_settings(), $settings ), array_keys( $this->get_public_post_types() ) );
	}

	/**
	 * Get public post types available for read mode.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_public_post_types() {
		if ( null !== $this->public_post_types_cache ) {
			return $this->public_post_types_cache;
		}

		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$options    = [];

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = [
				'slug'  => $post_type->name,
				'label' => $post_type->labels->singular_name,
			];
		}

		$this->public_post_types_cache = $options;

		return $this->public_post_types_cache;
	}

	/**
	 * Get allowed display locations.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_display_locations() {
		return Reader::get_display_location_options();
	}

	/**
	 * Get additional plugin cards.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_more_plugins() {
		return $this->plugin_catalog->get_cards();
	}

	/**
	 * Legacy protected wrappers retained for settings subclasses.
	 *
	 * @return array
	 */
	protected function get_free_plugin_catalog() {
		return $this->plugin_catalog->get_free_plugins();
	}

	/**
	 * @return array
	 */
	protected function get_paid_plugin_catalog() {
		return $this->plugin_catalog->get_paid_plugins();
	}

	/**
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return array
	 */
	protected function prepare_free_plugin_card( $plugin ) {
		return $this->plugin_catalog->prepare_free_plugin_card( $plugin );
	}

	/**
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return string
	 */
	protected function get_free_plugin_status( $plugin ) {
		return $this->plugin_catalog->get_free_plugin_status( $plugin );
	}

	/**
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return true|WP_Error
	 */
	protected function install_plugin_from_wordpress_org( $plugin ) {
		return $this->plugin_actions->install( $plugin );
	}

	/**
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return true|WP_Error
	 */
	protected function activate_free_plugin( $plugin ) {
		return $this->plugin_actions->activate( $plugin );
	}

	/**
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return string
	 */
	protected function get_installed_plugin_file( $plugin ) {
		return $this->plugin_catalog->get_installed_plugin_file( $plugin );
	}

	/**
	 * @param string $slug Plugin slug.
	 *
	 * @return bool
	 */
	protected function is_plugin_active_by_slug( $slug ) {
		return $this->plugin_catalog->is_plugin_active_by_slug( $slug );
	}

	/**
	 * @return array
	 */
	protected function get_installed_plugins() {
		return $this->plugin_catalog->get_installed_plugins();
	}

	/**
	 * @return void
	 */
	protected function invalidate_plugin_request_cache() {
		$this->plugin_catalog->invalidate_request_cache();
	}

	/**
	 * @param string $plugin_file Plugin file.
	 *
	 * @return bool
	 */
	protected function is_plugin_active_file( $plugin_file ) {
		return $this->plugin_catalog->is_plugin_active_file( $plugin_file );
	}

	/**
	 * @return void
	 */
	protected function load_plugin_admin_functions() {
		$this->plugin_catalog->load_plugin_admin_functions();
	}

	protected function invalidate_settings_request_cache() {
		$this->public_post_types_cache = null;
		Reader::invalidate_request_cache();
		Templates::invalidate_request_cache();
	}

	/**
	 * Sanitize settings data.
	 *
	 * @since 1.7.0
	 *
	 * @param array $data Settings data to sanitize.
	 *
	 * @return array
	 */
	protected function sanitize_settings_data( $data ) {
		return Reader::sanitize_settings_data( $data, array_keys( $this->get_public_post_types() ) );
	}
}
