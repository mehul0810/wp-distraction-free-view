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
	 * Installed plugins cache for the current request.
	 *
	 * @since 1.8.0
	 *
	 * @var array|null
	 */
	protected $installed_plugins_cache = null;

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
		$slug    = sanitize_key( $request['slug'] );
		$action  = sanitize_key( $request['action'] );
		$catalog = $this->get_free_plugin_catalog();

		if ( ! isset( $catalog[ $slug ] ) ) {
			return new WP_Error(
				'wpdfv_unknown_plugin',
				__( 'Plugin is not available from this screen.', 'wp-distraction-free-view' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'install' === $action ) {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return new WP_Error(
					'wpdfv_install_plugin_forbidden',
					__( 'Sorry, you are not allowed to install plugins.', 'wp-distraction-free-view' ),
					[ 'status' => 403 ]
				);
			}

			$result = $this->install_plugin_from_wordpress_org( $catalog[ $slug ] );
		} elseif ( 'activate' === $action ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return new WP_Error(
					'wpdfv_activate_plugin_forbidden',
					__( 'Sorry, you are not allowed to activate plugins.', 'wp-distraction-free-view' ),
					[ 'status' => 403 ]
				);
			}

			$result = $this->activate_free_plugin( $catalog[ $slug ] );
		} else {
			return new WP_Error(
				'wpdfv_unknown_plugin_action',
				__( 'Plugin action is not supported.', 'wp-distraction-free-view' ),
				[ 'status' => 400 ]
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->get_settings_response();
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
		$free_plugins = array_filter(
			$this->get_free_plugin_catalog(),
			function ( $plugin ) {
				if ( empty( $plugin['requires_active_slug'] ) ) {
					return true;
				}

				return $this->is_plugin_active_by_slug( $plugin['requires_active_slug'] );
			}
		);

		return [
			'free' => array_values( array_map( [ $this, 'prepare_free_plugin_card' ], $free_plugins ) ),
			'paid' => array_values( $this->get_paid_plugin_catalog() ),
		];
	}

	/**
	 * Get free companion plugins.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_free_plugin_catalog() {
		return [
			'perform'                 => [
				'slug'        => 'perform',
				'plugin_file' => 'perform/perform.php',
				'label'       => esc_html__( 'Perform', 'wp-distraction-free-view' ),
				'description' => esc_html__( 'Improve WordPress performance with focused caching and runtime optimizations.', 'wp-distraction-free-view' ),
				'wp_org_url'  => 'https://wordpress.org/plugins/perform',
				'website_url' => 'https://performwp.com',
			],
			'klaive'                  => [
				'slug'                 => 'klaive',
				'plugin_file'          => 'klaive/klaive.php',
				'label'                => esc_html__( 'Klaive', 'wp-distraction-free-view' ),
				'description'          => esc_html__( 'Connect GiveWP donation activity with Klaviyo email audiences.', 'wp-distraction-free-view' ),
				'wp_org_url'           => 'https://wordpress.org/plugins/klaive',
				'requires_active_slug' => 'give',
			],
			'cleanlinks'              => [
				'slug'        => 'cleanlinks',
				'plugin_file' => 'cleanlinks/cleanlinks.php',
				'label'       => esc_html__( 'CleanLinks', 'wp-distraction-free-view' ),
				'description' => esc_html__( 'Create cleaner, branded links from inside WordPress.', 'wp-distraction-free-view' ),
				'wp_org_url'  => 'https://wordpress.org/plugins/cleanlinks',
			],
			'mg-instamojo-for-givewp' => [
				'slug'                 => 'mg-instamojo-for-givewp',
				'plugin_file'          => 'mg-instamojo-for-givewp/mg-instamojo-for-givewp.php',
				'label'                => esc_html__( 'MG - Instamojo for GiveWP', 'wp-distraction-free-view' ),
				'description'          => esc_html__( 'Accept Instamojo payments in GiveWP donation forms.', 'wp-distraction-free-view' ),
				'wp_org_url'           => 'https://wordpress.org/plugins/mg-instamojo-for-givewp',
				'requires_active_slug' => 'give',
			],
		];
	}

	/**
	 * Get paid companion plugins.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_paid_plugin_catalog() {
		return [
			[
				'type'        => 'paid',
				'slug'        => 'onecaptcha',
				'label'       => esc_html__( 'OneCaptcha', 'wp-distraction-free-view' ),
				'description' => esc_html__( 'Premium CAPTCHA protection built for focused WordPress forms and conversion flows.', 'wp-distraction-free-view' ),
				'websiteUrl'  => 'https://onecaptchawp.com',
				'url'         => 'https://onecaptchawp.com',
			],
			[
				'type'        => 'paid',
				'slug'        => 'themerouter',
				'label'       => esc_html__( 'ThemeRouter', 'wp-distraction-free-view' ),
				'description' => esc_html__( 'Route WordPress visitors to purpose-built theme experiences without duplicating sites.', 'wp-distraction-free-view' ),
				'websiteUrl'  => 'https://themerouter.com',
				'url'         => 'https://themerouter.com',
			],
		];
	}

	/**
	 * Prepare a free plugin card for the admin app.
	 *
	 * @since 1.7.0
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return array
	 */
	protected function prepare_free_plugin_card( $plugin ) {
		$status = $this->get_free_plugin_status( $plugin );

		return [
			'type'         => 'free',
			'slug'         => $plugin['slug'],
			'label'        => $plugin['label'],
			'description'  => $plugin['description'],
			'wordpressUrl' => $plugin['wp_org_url'],
			'websiteUrl'   => isset( $plugin['website_url'] ) ? $plugin['website_url'] : '',
			'url'          => $plugin['wp_org_url'],
			'status'       => $status,
			'canInstall'   => current_user_can( 'install_plugins' ),
			'canActivate'  => current_user_can( 'activate_plugins' ),
		];
	}

	/**
	 * Get a free plugin install status.
	 *
	 * @since 1.7.0
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return string
	 */
	protected function get_free_plugin_status( $plugin ) {
		$plugin_file = $this->get_installed_plugin_file( $plugin );

		if ( '' === $plugin_file ) {
			return 'not_installed';
		}

		if ( $this->is_plugin_active_file( $plugin_file ) ) {
			return 'active';
		}

		return 'installed';
	}

	/**
	 * Install a free plugin from WordPress.org.
	 *
	 * @since 1.7.0
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return true|WP_Error
	 */
	protected function install_plugin_from_wordpress_org( $plugin ) {
		$status = $this->get_free_plugin_status( $plugin );

		if ( 'not_installed' !== $status ) {
			return true;
		}

		$this->load_plugin_install_functions();

		$api = plugins_api(
			'plugin_information',
			[
				'slug'   => $plugin['slug'],
				'fields' => [
					'sections' => false,
				],
			]
		);

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		if ( empty( $api->download_link ) ) {
			return new WP_Error(
				'wpdfv_plugin_download_missing',
				__( 'Plugin download link could not be found.', 'wp-distraction-free-view' ),
				[ 'status' => 500 ]
			);
		}

		$skin     = new \Automatic_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			$errors = $skin->get_errors();

			if ( is_wp_error( $errors ) && $errors->has_errors() ) {
				return $errors;
			}

			return new WP_Error(
				'wpdfv_plugin_install_failed',
				__( 'Plugin could not be installed.', 'wp-distraction-free-view' ),
				[ 'status' => 500 ]
			);
		}

		$this->invalidate_plugin_request_cache();

		return true;
	}

	/**
	 * Activate a free companion plugin.
	 *
	 * @since 1.7.0
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return true|WP_Error
	 */
	protected function activate_free_plugin( $plugin ) {
		$plugin_file = $this->get_installed_plugin_file( $plugin );

		if ( '' === $plugin_file ) {
			return new WP_Error(
				'wpdfv_plugin_not_installed',
				__( 'Plugin must be installed before it can be activated.', 'wp-distraction-free-view' ),
				[ 'status' => 400 ]
			);
		}

		if ( $this->is_plugin_active_file( $plugin_file ) ) {
			return true;
		}

		$this->load_plugin_admin_functions();

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = activate_plugin( $plugin_file, '', false, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->invalidate_plugin_request_cache();

		return true;
	}

	/**
	 * Get the installed plugin file for a catalog item.
	 *
	 * @since 1.7.0
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return string
	 */
	protected function get_installed_plugin_file( $plugin ) {
		$installed_plugins = $this->get_installed_plugins();

		if ( isset( $installed_plugins[ $plugin['plugin_file'] ] ) ) {
			return $plugin['plugin_file'];
		}

		foreach ( array_keys( $installed_plugins ) as $plugin_file ) {
			if ( str_starts_with( $plugin_file, "{$plugin['slug']}/" ) ) {
				return $plugin_file;
			}
		}

		return '';
	}

	/**
	 * Check whether a plugin slug is active.
	 *
	 * @since 1.7.0
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return bool
	 */
	protected function is_plugin_active_by_slug( $slug ) {
		foreach ( array_keys( $this->get_installed_plugins() ) as $plugin_file ) {
			if ( str_starts_with( $plugin_file, "{$slug}/" ) && $this->is_plugin_active_file( $plugin_file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get installed plugins.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	protected function get_installed_plugins() {
		if ( null !== $this->installed_plugins_cache ) {
			return $this->installed_plugins_cache;
		}

		$this->load_plugin_admin_functions();

		if ( ! function_exists( 'get_plugins' ) ) {
			$this->installed_plugins_cache = [];

			return $this->installed_plugins_cache;
		}

		$this->installed_plugins_cache = get_plugins();

		return $this->installed_plugins_cache;
	}

	/**
	 * Clear per-request settings caches after settings are saved.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	protected function invalidate_settings_request_cache() {
		$this->public_post_types_cache = null;
		Reader::invalidate_request_cache();
		Templates::invalidate_request_cache();
	}

	/**
	 * Clear per-request plugin status caches after plugin actions.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	protected function invalidate_plugin_request_cache() {
		$this->installed_plugins_cache = null;
	}

	/**
	 * Check whether a plugin file is active.
	 *
	 * @since 1.7.0
	 *
	 * @param string $plugin_file Plugin file.
	 *
	 * @return bool
	 */
	protected function is_plugin_active_file( $plugin_file ) {
		$this->load_plugin_admin_functions();

		return function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file );
	}

	/**
	 * Load admin plugin functions when they are not available yet.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	protected function load_plugin_admin_functions() {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Load plugin installation dependencies.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	protected function load_plugin_install_functions() {
		$this->load_plugin_admin_functions();

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
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
