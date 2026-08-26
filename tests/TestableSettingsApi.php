<?php
/**
 * Testable settings API wrapper.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use WPDFV\Admin\SettingsApi;

/**
 * Exposes protected settings API methods for focused unit tests.
 */
class TestableSettingsApi extends SettingsApi {
	/**
	 * Expose More Plugins data.
	 *
	 * @return array
	 */
	public function get_more_plugins_for_tests() {
		return $this->get_more_plugins();
	}

	/**
	 * Expose public post type options.
	 *
	 * @return array
	 */
	public function get_public_post_types_for_tests() {
		return $this->get_public_post_types();
	}

	/**
	 * Expose installed plugin data.
	 *
	 * @return array
	 */
	public function get_installed_plugins_for_tests() {
		return $this->get_installed_plugins();
	}

	/**
	 * Activate a free companion plugin from the test catalog.
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return true|\WP_Error
	 */
	public function activate_free_plugin_for_tests( $slug ) {
		$catalog = $this->get_free_plugin_catalog();

		return $this->activate_free_plugin( $catalog[ $slug ] );
	}
}
