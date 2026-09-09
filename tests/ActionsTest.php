<?php
/**
 * Asset action tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use PHPUnit\Framework\TestCase;
use WPDFV\Admin\Actions as AdminActions;
use WPDFV\Includes\Actions as FrontendActions;

/**
 * Tests for frontend and admin asset registration.
 */
class ActionsTest extends TestCase {
	/**
	 * Reset test state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		\wpdfv_tests_reset_state();
	}

	/**
	 * Frontend styles opt into WordPress RTL replacement.
	 *
	 * @return void
	 */
	public function test_frontend_style_uses_rtl_replacement() {
		FrontendActions::register_frontend_assets();

		$this->assertSame( 'replace', $GLOBALS['wpdfv_test_enqueued']['style_data']['wpdfv-core']['rtl'] );
	}

	/**
	 * Admin styles opt into WordPress RTL replacement.
	 *
	 * @return void
	 */
	public function test_admin_style_uses_rtl_replacement() {
		$actions = new AdminActions();

		$actions->register_admin_assets( 'settings_page_wpdfv_settings' );

		$this->assertSame( 'replace', $GLOBALS['wpdfv_test_enqueued']['style_data']['wpdfv-admin']['rtl'] );
	}
}
