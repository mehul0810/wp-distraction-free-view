<?php
/**
 * WP Distraction Free View | companion plugin catalog.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the static companion catalog and current installation state.
 */
class MorePluginsCatalog {

	/**
	 * Installed plugins cache for the current request.
	 *
	 * @var array|null
	 */
	protected $installed_plugins_cache = null;

	/**
	 * Get plugin cards for the More Plugins tab.
	 *
	 * @return array
	 */
	public function get_cards() {
		$free_plugins = array_filter(
			$this->get_free_plugins(),
			function ( $plugin ) {
				return empty( $plugin['requires_active_slug'] ) || $this->is_plugin_active_by_slug( $plugin['requires_active_slug'] );
			}
		);

		return [
			'free' => array_values( array_map( [ $this, 'prepare_free_plugin_card' ], $free_plugins ) ),
			'paid' => array_values( $this->get_paid_plugins() ),
		];
	}

	/**
	 * Get the installable free plugin catalog.
	 *
	 * @return array
	 */
	public function get_free_plugins() {
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
	 * Get companion plugins promoted as paid.
	 *
	 * @return array
	 */
	public function get_paid_plugins() {
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
	 * Prepare a free plugin card for the admin settings app.
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return array
	 */
	public function prepare_free_plugin_card( $plugin ) {
		return [
			'type'         => 'free',
			'slug'         => $plugin['slug'],
			'label'        => $plugin['label'],
			'description'  => $plugin['description'],
			'wordpressUrl' => $plugin['wp_org_url'],
			'websiteUrl'   => isset( $plugin['website_url'] ) ? $plugin['website_url'] : '',
			'url'          => $plugin['wp_org_url'],
			'status'       => $this->get_free_plugin_status( $plugin ),
			'canInstall'   => current_user_can( 'install_plugins' ),
			'canActivate'  => current_user_can( 'activate_plugins' ),
		];
	}

	/**
	 * Get a free plugin installation status.
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return string
	 */
	public function get_free_plugin_status( $plugin ) {
		$plugin_file = $this->get_installed_plugin_file( $plugin );

		if ( '' === $plugin_file ) {
			return 'not_installed';
		}

		return $this->is_plugin_active_file( $plugin_file ) ? 'active' : 'installed';
	}

	/**
	 * Find an installed plugin file belonging to a catalog item.
	 *
	 * @param array $plugin Plugin catalog item.
	 *
	 * @return string
	 */
	public function get_installed_plugin_file( $plugin ) {
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
	 * @param string $slug Plugin slug.
	 *
	 * @return bool
	 */
	public function is_plugin_active_by_slug( $slug ) {
		foreach ( array_keys( $this->get_installed_plugins() ) as $plugin_file ) {
			if ( str_starts_with( $plugin_file, "{$slug}/" ) && $this->is_plugin_active_file( $plugin_file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get installed plugins for the current request.
	 *
	 * @return array
	 */
	public function get_installed_plugins() {
		if ( null !== $this->installed_plugins_cache ) {
			return $this->installed_plugins_cache;
		}

		$this->load_plugin_admin_functions();

		$this->installed_plugins_cache = function_exists( 'get_plugins' ) ? get_plugins() : [];

		return $this->installed_plugins_cache;
	}

	/**
	 * Invalidate per-request plugin status data.
	 *
	 * @return void
	 */
	public function invalidate_request_cache() {
		$this->installed_plugins_cache = null;
	}

	/**
	 * Check whether a plugin file is active.
	 *
	 * @param string $plugin_file Plugin file.
	 *
	 * @return bool
	 */
	public function is_plugin_active_file( $plugin_file ) {
		$this->load_plugin_admin_functions();

		return function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file );
	}

	/**
	 * Load core plugin administration helpers when needed.
	 *
	 * @return void
	 */
	public function load_plugin_admin_functions() {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
