<?php
/**
 * WordPress activation and multisite integration tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WPDFV\Admin\MorePluginsCatalog;
use WPDFV\Admin\PluginActions;
use WPDFV\Includes\Reader;
use WPDFV\Includes\Templates;
use WPDFV\Plugin;

class PluginActionsIntegrationTest extends TestCase {
	/**
	 * A real companion activation must run its setup hook once.
	 */
	public function test_companion_activation_runs_activation_hook() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		wp_set_current_user( 1 );
		$slug        = 'wpdfv-test-' . wp_generate_password( 8, false );
		$directory   = WP_PLUGIN_DIR . '/' . $slug;
		$plugin_file = $slug . '/fixture.php';
		$marker      = 'wpdfv_activation_fixture';
		wp_mkdir_p( $directory );
		file_put_contents(
			$directory . '/fixture.php',
			"<?php\n/* Plugin Name: WPDFV Activation Fixture */\nregister_activation_hook( __FILE__, function() { update_option( 'wpdfv_activation_fixture', 1 + (int) get_option( 'wpdfv_activation_fixture', 0 ) ); } );\n"
		);
		wp_clean_plugins_cache();
		delete_option( $marker );

		try {
			$actions = new PluginActions(
				new MorePluginsCatalog(),
				static function () {
					return [ 'success' => true ];
				}
			);
			$plugin  = [
				'slug'        => $slug,
				'plugin_file' => $plugin_file,
			];

			$deny_activation = static function ( $caps, $cap, $user_id, $args ) use ( $plugin_file ) {
				if ( 'activate_plugin' === $cap && isset( $args[0] ) && $plugin_file === $args[0] ) {
					return [ 'do_not_allow' ];
				}
				return $caps;
			};
			add_filter( 'map_meta_cap', $deny_activation, 10, 4 );
			try {
				$denied = $actions->activate( $plugin );
				$this->assertInstanceOf( \WP_Error::class, $denied );
				$this->assertSame( 'wpdfv_activate_plugin_forbidden', $denied->get_error_code() );
				$this->assertFalse( is_plugin_active( $plugin_file ) );
				$this->assertFalse( get_option( $marker, false ) );
			} finally {
				remove_filter( 'map_meta_cap', $deny_activation, 10 );
			}

			$this->assertTrue( $actions->activate( $plugin ) );
			$this->assertSame( 1, (int) get_option( $marker ) );
			$this->assertTrue( $actions->activate( $plugin ) );
			$this->assertSame( 1, (int) get_option( $marker ) );
		} finally {
			deactivate_plugins( $plugin_file, true );
			delete_option( $marker );
			unlink( $directory . '/fixture.php' );
			rmdir( $directory );
			wp_clean_plugins_cache();
			wp_set_current_user( 0 );
		}
	}

	/**
	 * A site activation must never activate a network-only plugin.
	 *
	 * @group ms-required
	 */
	public function test_network_only_plugin_is_rejected_without_state_changes() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires a multisite installation.' );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		wp_set_current_user( 1 );
		$slug        = 'wpdfv-network-test-' . wp_generate_password( 8, false );
		$directory   = WP_PLUGIN_DIR . '/' . $slug;
		$plugin_file = $slug . '/fixture.php';
		wp_mkdir_p( $directory );
		file_put_contents(
			$directory . '/fixture.php',
			"<?php\n/*\nPlugin Name: WPDFV Network Fixture\nNetwork: true\n*/\n"
		);
		wp_clean_plugins_cache();
		$before = get_site_option( 'active_sitewide_plugins', [] );

		try {
			$actions = new PluginActions( new MorePluginsCatalog(), static function () {} );
			$result  = $actions->activate(
				[
					'slug'        => $slug,
					'plugin_file' => $plugin_file,
				]
			);
			$this->assertInstanceOf( \WP_Error::class, $result );
			$this->assertSame( 'wpdfv_network_only_plugin', $result->get_error_code() );
			$this->assertFalse( is_plugin_active( $plugin_file ) );
			$this->assertSame( $before, get_site_option( 'active_sitewide_plugins', [] ) );
		} finally {
			unlink( $directory . '/fixture.php' );
			rmdir( $directory );
			wp_clean_plugins_cache();
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Network activation initializes only the current network's sites.
	 *
	 * @group ms-required
	 */
	public function test_network_activation_preserves_other_network_sites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires a multisite installation.' );
		}

		global $wpdb;

		$original_settings = get_option( 'wpdfv_settings', false );
		$original_version  = get_option( 'wpdfv_version', false );
		$site_ids          = [];
		$network_id        = 0;

		try {
			$inserted = $wpdb->insert(
				$wpdb->site,
				[
					'domain' => 'wpdfv-network-' . wp_rand( 1000, 999999 ) . '.example.org',
					'path'   => '/',
				]
			);
			$this->assertSame( 1, $inserted );
			$network_id = (int) $wpdb->insert_id;
			$this->assertNotSame( get_current_network_id(), $network_id );

			foreach ( [ get_current_network_id(), $network_id ] as $target_network_id ) {
				$site_id = wp_insert_site(
					[
						'domain'     => 'wpdfv-site-' . wp_rand( 1000, 999999 ) . '.example.org',
						'path'       => '/',
						'network_id' => $target_network_id,
					]
				);
				$this->assertFalse( is_wp_error( $site_id ) );
				$site_ids[] = (int) $site_id;
			}

			switch_to_blog( $site_ids[0] );
			delete_option( 'wpdfv_settings' );
			delete_option( 'wpdfv_version' );
			restore_current_blog();

			switch_to_blog( $site_ids[1] );
			$other_settings = [ 'button_text' => 'Other network settings' ];
			update_option( 'wpdfv_settings', $other_settings );
			delete_option( 'wpdfv_version' );
			restore_current_blog();

			( new Plugin() )->activate( true );

			switch_to_blog( $site_ids[0] );
			$this->assertSame( WPDFV_VERSION, get_option( 'wpdfv_version' ) );
			$this->assertSame( Reader::get_default_settings(), get_option( 'wpdfv_settings' ) );
			restore_current_blog();

			switch_to_blog( $site_ids[1] );
			$this->assertSame( $other_settings, get_option( 'wpdfv_settings' ) );
			$this->assertFalse( get_option( 'wpdfv_version', false ) );
			restore_current_blog();
		} finally {
			while ( ms_is_switched() ) {
				restore_current_blog();
			}
			foreach ( $site_ids as $site_id ) {
				wp_delete_site( $site_id );
			}
			if ( $network_id ) {
				$wpdb->delete( $wpdb->sitemeta, [ 'site_id' => $network_id ] );
				$wpdb->delete( $wpdb->site, [ 'id' => $network_id ] );
				clean_network_cache( $network_id );
			}
			if ( false === $original_settings ) {
				delete_option( 'wpdfv_settings' );
			} else {
				update_option( 'wpdfv_settings', $original_settings );
			}
			if ( false === $original_version ) {
				delete_option( 'wpdfv_version' );
			} else {
				update_option( 'wpdfv_version', $original_version );
			}
		}
	}

	/**
	 * Site-specific settings follow switch_to_blog and restore_current_blog.
	 *
	 * @group ms-required
	 */
	public function test_site_switch_invalidates_reader_settings() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires a multisite installation.' );
		}

		$site_id = wp_insert_site(
			[
				'domain' => 'wpdfv-test-' . wp_rand( 1000, 999999 ) . '.example.org',
				'path'   => '/',
			]
		);
		$this->assertFalse( is_wp_error( $site_id ) );
		$original_settings = get_option( 'wpdfv_settings', false );
		$template_filter   = static function ( $templates ) {
			$templates['site-template'] = [
				'label'   => 'Site ' . get_current_blog_id(),
				'content' => '<!-- wp:post-content /-->',
			];
			return $templates;
		};
		add_filter( 'wpdfv_modal_templates', $template_filter );
		try {
			update_option( 'wpdfv_settings', [ 'button_text' => 'Site A' ] );
			switch_to_blog( $site_id );
			update_option( 'wpdfv_settings', [ 'button_text' => 'Site B' ] );
			restore_current_blog();

			$this->assertSame( 'Site A', Reader::get_settings()['button_text'] );
			$site_a_template = 'Site ' . get_current_blog_id();
			$this->assertSame( $site_a_template, Templates::get_registered_templates()['site-template']['label'] );
			$this->assertSame( $site_a_template, array_column( Templates::get_template_options(), 'label', 'value' )['site-template'] );
			switch_to_blog( $site_id );
			$this->assertSame( 'Site B', Reader::get_settings()['button_text'] );
			$this->assertSame( 'Site ' . $site_id, Templates::get_registered_templates()['site-template']['label'] );
			$this->assertSame( 'Site ' . $site_id, array_column( Templates::get_template_options(), 'label', 'value' )['site-template'] );
			restore_current_blog();
			$this->assertSame( 'Site A', Reader::get_settings()['button_text'] );
			$this->assertSame( $site_a_template, Templates::get_registered_templates()['site-template']['label'] );
			$this->assertSame( $site_a_template, array_column( Templates::get_template_options(), 'label', 'value' )['site-template'] );
		} finally {
			while ( ms_is_switched() ) {
				restore_current_blog();
			}
			remove_filter( 'wpdfv_modal_templates', $template_filter );
			Templates::invalidate_request_cache();
			if ( false === $original_settings ) {
				delete_option( 'wpdfv_settings' );
			} else {
				update_option( 'wpdfv_settings', $original_settings );
			}
			wp_delete_site( $site_id );
		}
	}
}
