<?php
/**
 * WP Distraction Free View | companion plugin actions.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Admin;

use WP_Error;
use WP_REST_Request;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs capability-checked installation and activation for catalog items.
 */
class PluginActions {

	/**
	 * Companion plugin catalog.
	 *
	 * @var MorePluginsCatalog
	 */
	protected $catalog;

	/**
	 * Settings response callback.
	 *
	 * @var callable
	 */
	protected $settings_response;

	/**
	 * Constructor.
	 *
	 * @param MorePluginsCatalog $catalog           Plugin catalog.
	 * @param callable           $settings_response Settings API response callback.
	 */
	public function __construct( MorePluginsCatalog $catalog, $settings_response ) {
		$this->catalog           = $catalog;
		$this->settings_response = $settings_response;
	}

	/**
	 * Handle a catalog install or activate action.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return mixed
	 */
	public function handle( WP_REST_Request $request ) {
		$slug    = sanitize_key( $request['slug'] );
		$action  = sanitize_key( $request['action'] );
		$catalog = $this->catalog->get_free_plugins();

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

			$result = $this->install( $catalog[ $slug ] );
		} elseif ( 'activate' === $action ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return new WP_Error(
					'wpdfv_activate_plugin_forbidden',
					__( 'Sorry, you are not allowed to activate plugins.', 'wp-distraction-free-view' ),
					[ 'status' => 403 ]
				);
			}

			$result = $this->activate( $catalog[ $slug ] );
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

		return call_user_func( $this->settings_response );
	}

	/**
	 * Install a catalog plugin from WordPress.org.
	 *
	 * @param array $plugin Catalog item.
	 *
	 * @return true|WP_Error
	 */
	public function install( $plugin ) {
		if ( 'not_installed' !== $this->catalog->get_free_plugin_status( $plugin ) ) {
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

		$this->catalog->invalidate_request_cache();

		return true;
	}

	/**
	 * Activate an installed catalog plugin.
	 *
	 * @param array $plugin Catalog item.
	 *
	 * @return true|WP_Error
	 */
	public function activate( $plugin ) {
		$plugin_file = $this->catalog->get_installed_plugin_file( $plugin );

		if ( '' === $plugin_file ) {
			return new WP_Error(
				'wpdfv_plugin_not_installed',
				__( 'Plugin must be installed before it can be activated.', 'wp-distraction-free-view' ),
				[ 'status' => 400 ]
			);
		}

		if ( $this->catalog->is_plugin_active_file( $plugin_file ) ) {
			return true;
		}

		$this->catalog->load_plugin_admin_functions();

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = activate_plugin( $plugin_file, '', false, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->catalog->invalidate_request_cache();

		return true;
	}

	/**
	 * Load the WordPress plugin installation dependencies.
	 *
	 * @return void
	 */
	protected function load_plugin_install_functions() {
		$this->catalog->load_plugin_admin_functions();

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
	}
}
