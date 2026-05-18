<?php
/**
 * Reader Mode unit tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use PHPUnit\Framework\TestCase;
use WPDFV\Admin\Upgrades;
use WPDFV\Includes\Blocks;
use WPDFV\Includes\Helpers;
use WPDFV\Includes\Reader;
use WPDFV\Includes\Shortcodes\Main;
use WPDFV\Includes\Templates;
use WPDFV\Plugin;

/**
 * Tests for Reader Mode settings, upgrades, and rendering helpers.
 */
class ReaderTest extends TestCase {
	/**
	 * Reset test state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		\wpdfv_tests_reset_state();
	}

	/**
	 * Defaults describe the new frontend Reader Mode experience.
	 *
	 * @return void
	 */
	public function test_default_settings() {
		$defaults = Reader::get_default_settings();

		$this->assertFalse( $defaults['automatic_button_enabled'] );
		$this->assertSame( 'manual_only', $defaults['display_location'] );
		$this->assertSame( [ 'post', 'page' ], $defaults['where_to_display'] );
		$this->assertSame( 'Read in Reader Mode', $defaults['button_text'] );
		$this->assertSame( 'Exit Reader Mode', $defaults['exit_button_text'] );
		$this->assertTrue( $defaults['reading_progress_enabled'] );
		$this->assertTrue( $defaults['reading_time_enabled'] );
		$this->assertTrue( $defaults['preference_controls_enabled'] );
	}

	/**
	 * Sanitization rejects unknown values and keeps rollback-safe defaults.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_data() {
		$settings = Reader::sanitize_settings_data(
			[
				'automatic_button_enabled'    => true,
				'where_to_display'            => [ 'post', 'bad type', 'book', 'book' ],
				'display_location'            => 'floating',
				'button_text'                 => '<strong>Read</strong>',
				'exit_button_text'            => '',
				'modal_template'              => 'missing',
				'reading_progress_enabled'    => 0,
				'reading_time_enabled'        => 1,
				'preference_controls_enabled' => true,
				'default_reader_theme'        => 'neon',
				'default_content_width'       => 'wide',
				'default_font_size'           => 'large',
			],
			[ 'post', 'page', 'book' ]
		);

		$this->assertTrue( $settings['automatic_button_enabled'] );
		$this->assertSame( [ 'post', 'book' ], $settings['where_to_display'] );
		$this->assertSame( 'floating', $settings['display_location'] );
		$this->assertSame( 'Read', $settings['button_text'] );
		$this->assertSame( 'Exit Reader Mode', $settings['exit_button_text'] );
		$this->assertSame( Templates::DEFAULT_TEMPLATE, $settings['modal_template'] );
		$this->assertFalse( $settings['reading_progress_enabled'] );
		$this->assertTrue( $settings['reading_time_enabled'] );
		$this->assertSame( 'light', $settings['default_reader_theme'] );
		$this->assertSame( 'wide', $settings['default_content_width'] );
		$this->assertSame( 'large', $settings['default_font_size'] );
	}

	/**
	 * Manual-only placement always disables automatic insertion.
	 *
	 * @return void
	 */
	public function test_manual_only_disables_automatic_insertion() {
		$settings = Reader::sanitize_settings_data(
			[
				'automatic_button_enabled' => true,
				'display_location'         => 'manual_only',
			],
			[ 'post', 'page' ]
		);

		$this->assertFalse( $settings['automatic_button_enabled'] );
	}

	/**
	 * URL activation recognizes public Reader Mode values only.
	 *
	 * @return void
	 */
	public function test_reader_mode_query_activation() {
		$_GET['reader-mode'] = 'yes';
		$this->assertTrue( Reader::is_reader_mode_request() );

		$_GET['reader-mode'] = '0';
		$this->assertFalse( Reader::is_reader_mode_request() );
	}

	/**
	 * Reading time uses the documented word-count estimate.
	 *
	 * @return void
	 */
	public function test_calculate_reading_time() {
		$this->assertSame( 1, Reader::calculate_reading_time( '' ) );
		$this->assertSame( 3, Reader::calculate_reading_time( str_repeat( 'word ', 401 ) ) );
	}

	/**
	 * Reader toggle rendering keeps the legacy class and adds new semantics.
	 *
	 * @return void
	 */
	public function test_reader_toggle_markup() {
		$markup = Helpers::display_read_mode_button( 123 );

		$this->assertStringContainsString( 'wpdfv-fullscreen-btn', $markup );
		$this->assertStringContainsString( 'wpdfv-reader-toggle', $markup );
		$this->assertStringContainsString( 'data-post-id="123"', $markup );
		$this->assertStringContainsString( 'Read in Reader Mode', $markup );
		$this->assertStringContainsString( 'aria-haspopup="dialog"', $markup );
	}

	/**
	 * The plugin keeps the master branch shortcode surface: [wpdfv] only.
	 *
	 * @return void
	 */
	public function test_only_master_shortcode_is_registered() {
		new Main();

		$this->assertSame( [ 'wpdfv' ], array_keys( $GLOBALS['wpdfv_test_shortcodes'] ) );
	}

	/**
	 * Legacy installs migrate incrementally and idempotently.
	 *
	 * @return void
	 */
	public function test_upgrade_migrates_legacy_options_idempotently() {
		\update_option( 'wpdfv_version', '1.5.0', false );
		\update_option(
			'wpdfv_general',
			[
				'display_read_mode_at' => 'before_content',
				'read_mode_btn_text'   => 'Focus view',
			],
			false
		);
		\update_option(
			'wpdfv_settings',
			[
				'where_to_display' => [ 'post', 'book' ],
			],
			false
		);

		$upgrades = new Upgrades();
		$upgrades->process_automatic_upgrades();

		$settings = \get_option( 'wpdfv_settings' );

		$this->assertSame( WPDFV_VERSION, \get_option( 'wpdfv_version' ) );
		$this->assertSame( 'before_content', $settings['display_location'] );
		$this->assertSame( 'Focus view', $settings['button_text'] );
		$this->assertTrue( $settings['automatic_button_enabled'] );
		$this->assertSame( [ 'post', 'book' ], $settings['where_to_display'] );
		$this->assertSame( 'Exit Reader Mode', $settings['exit_button_text'] );
		$this->assertTrue( $settings['reading_progress_enabled'] );

		$upgrades->process_automatic_upgrades();

		$this->assertSame( $settings, \get_option( 'wpdfv_settings' ) );
	}

	/**
	 * Development-only 2.x markers are normalized to the 1.7.0 release path.
	 *
	 * @return void
	 */
	public function test_upgrade_normalizes_unreleased_two_x_version_marker() {
		\update_option( 'wpdfv_version', '2.2.0', false );
		\update_option(
			'wpdfv_settings',
			[
				'display_location' => 'disable',
				'button_text'      => 'Read Mode',
			],
			false
		);

		$upgrades = new Upgrades();
		$upgrades->process_automatic_upgrades();

		$settings = \get_option( 'wpdfv_settings' );

		$this->assertSame( '1.7.0', \get_option( 'wpdfv_version' ) );
		$this->assertSame( 'manual_only', $settings['display_location'] );
		$this->assertFalse( $settings['automatic_button_enabled'] );
		$this->assertSame( 'Read Mode', $settings['button_text'] );
		$this->assertSame( 'Exit Reader Mode', $settings['exit_button_text'] );
	}

	/**
	 * Activation must not skip migrations for old inactive installs.
	 *
	 * @return void
	 */
	public function test_activation_keeps_legacy_install_pending_for_upgrade() {
		\update_option(
			'wpdfv_general',
			[
				'display_read_mode_at' => 'before_content',
				'read_mode_btn_text'   => 'Legacy label',
			],
			false
		);

		$plugin = new Plugin();
		$plugin->activate();

		$this->assertSame( '1.0.0', \get_option( 'wpdfv_version' ) );

		$upgrades = new Upgrades();
		$upgrades->process_automatic_upgrades();

		$settings = \get_option( 'wpdfv_settings' );

		$this->assertSame( WPDFV_VERSION, \get_option( 'wpdfv_version' ) );
		$this->assertSame( 'before_content', $settings['display_location'] );
		$this->assertSame( 'Legacy label', $settings['button_text'] );
	}

	/**
	 * Reader access must respect the enabled post type list.
	 *
	 * @return void
	 */
	public function test_reader_content_access_requires_enabled_post_type() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'where_to_display' => [ 'post' ],
				]
			),
			false
		);

		$post            = new \WP_Post();
		$post->ID        = 42;
		$post->post_type = 'book';

		$shortcode = new class() extends Main {
			public function can_read_post_for_tests( \WP_Post $post ) {
				return $this->can_read_post( $post );
			}
		};

		$this->assertFalse( $shortcode->can_read_post_for_tests( $post ) );
	}

	/**
	 * Manual shortcode output should not create toggles for disabled post types.
	 *
	 * @return void
	 */
	public function test_shortcode_does_not_render_for_disabled_post_type() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'where_to_display' => [ 'post' ],
				]
			),
			false
		);

		$post            = new \WP_Post();
		$post->ID        = 42;
		$post->post_type = 'book';

		$GLOBALS['wpdfv_test_posts'][42] = $post;

		$shortcode = new Main();

		$this->assertSame( '', $shortcode->render_shortcode( [ 'post_id' => 42 ] ) );
	}

	/**
	 * Dynamic block output should enqueue the shared frontend handle once.
	 *
	 * @return void
	 */
	public function test_reader_block_uses_shared_frontend_assets() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'where_to_display' => [ 'post' ],
				]
			),
			false
		);

		$block = (object) [
			'context' => [
				'postId'   => 42,
				'postType' => 'post',
			],
		];

		$blocks = new Blocks();
		$markup = $blocks->render_reader_button( [], '', $block );

		$this->assertStringContainsString( 'data-post-id="42"', $markup );
		$this->assertContains( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['scripts'] );
		$this->assertArrayHasKey( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['inline'] );
	}

	/**
	 * More Plugins groups free and paid cards with install status.
	 *
	 * @return void
	 */
	public function test_more_plugins_groups_free_and_paid_cards() {
		$GLOBALS['wpdfv_test_plugins']        = [
			'perform/perform.php'       => [ 'Name' => 'Perform' ],
			'cleanlinks/cleanlinks.php' => [ 'Name' => 'CleanLinks' ],
		];
		$GLOBALS['wpdfv_test_active_plugins'] = [ 'perform/perform.php' ];

		$settings_api = new TestableSettingsApi();
		$plugins      = $settings_api->get_more_plugins_for_tests();

		$this->assertSame( [ 'perform', 'cleanlinks' ], array_column( $plugins['free'], 'slug' ) );
		$this->assertSame( [ 'onecaptcha', 'themerouter' ], array_column( $plugins['paid'], 'slug' ) );
		$this->assertSame( 'active', $plugins['free'][0]['status'] );
		$this->assertSame( 'installed', $plugins['free'][1]['status'] );
	}

	/**
	 * GiveWP companion plugins are only shown when GiveWP is active.
	 *
	 * @return void
	 */
	public function test_more_plugins_shows_givewp_integrations_when_givewp_is_active() {
		$GLOBALS['wpdfv_test_plugins']        = [
			'give/give.php' => [ 'Name' => 'GiveWP' ],
		];
		$GLOBALS['wpdfv_test_active_plugins'] = [ 'give/give.php' ];

		$settings_api = new TestableSettingsApi();
		$plugins      = $settings_api->get_more_plugins_for_tests();

		$this->assertSame(
			[ 'perform', 'klaive', 'cleanlinks', 'mg-instamojo-for-givewp' ],
			array_column( $plugins['free'], 'slug' )
		);
	}

	/**
	 * Free companion plugins can be activated from the settings API.
	 *
	 * @return void
	 */
	public function test_more_plugins_can_activate_installed_free_plugin() {
		$GLOBALS['wpdfv_test_plugins'] = [
			'cleanlinks/cleanlinks.php' => [ 'Name' => 'CleanLinks' ],
		];

		$settings_api = new TestableSettingsApi();
		$result       = $settings_api->activate_free_plugin_for_tests( 'cleanlinks' );
		$plugins      = $settings_api->get_more_plugins_for_tests();

		$this->assertTrue( $result );
		$this->assertSame( [ 'cleanlinks/cleanlinks.php' ], $GLOBALS['wpdfv_test_active_plugins'] );
		$this->assertSame( 'active', $plugins['free'][1]['status'] );
	}
}
