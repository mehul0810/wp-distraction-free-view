<?php
/**
 * Tests for companion-plugin activation.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use PHPUnit\Framework\TestCase;
use WPDFV\Admin\MorePluginsCatalog;
use WPDFV\Admin\PluginActions;

require_once dirname( __DIR__ ) . '/src/Admin/MorePluginsCatalog.php';
require_once dirname( __DIR__ ) . '/src/Admin/PluginActions.php';

class PluginActionsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		wpdfv_tests_reset_state();
		$GLOBALS['wpdfv_test_plugins'] = [
			'perform/perform.php' => [ 'Name' => 'Perform' ],
		];
	}

	public function test_activation_checks_the_selected_plugin_capability() {
		$GLOBALS['wpdfv_test_user_caps'] = [
			'activate_plugins' => true,
			'activate_plugin'  => false,
		];
		$actions                         = new PluginActions( new MorePluginsCatalog(), static function () {} );
		$request                         = new \WP_REST_Request();
		$request->set_param( 'slug', 'perform' );
		$request->set_param( 'action', 'activate' );

		$result = $actions->handle( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wpdfv_activate_plugin_forbidden', $result->get_error_code() );
		$this->assertSame( [], $GLOBALS['wpdfv_test_activation_calls'] );
		$this->assertSame( [], $GLOBALS['wpdfv_test_active_plugins'] );
	}

	public function test_network_only_plugin_cannot_be_activated_from_a_site() {
		$GLOBALS['wpdfv_test_multisite']            = true;
		$GLOBALS['wpdfv_test_network_only_plugins'] = [ 'perform/perform.php' ];
		$actions                                    = new PluginActions( new MorePluginsCatalog(), static function () {} );
		$request                                    = new \WP_REST_Request();
		$request->set_param( 'slug', 'perform' );
		$request->set_param( 'action', 'activate' );

		$result = $actions->handle( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wpdfv_network_only_plugin', $result->get_error_code() );
		$this->assertSame( [], $GLOBALS['wpdfv_test_activation_calls'] );
		$this->assertSame( [], $GLOBALS['wpdfv_test_active_plugins'] );
	}

	public function test_allowed_activation_runs_hooks_and_is_idempotent() {
		$actions = new PluginActions(
			new MorePluginsCatalog(),
			static function () {
				return [ 'result' => 'success' ];
			}
		);
		$request = new \WP_REST_Request();
		$request->set_param( 'slug', 'perform' );
		$request->set_param( 'action', 'activate' );

		$this->assertSame( [ 'result' => 'success' ], $actions->handle( $request ) );
		$this->assertSame( [ 'result' => 'success' ], $actions->handle( $request ) );
		$this->assertSame( [ [ 'perform/perform.php', false, false ] ], $GLOBALS['wpdfv_test_activation_calls'] );
	}
}
