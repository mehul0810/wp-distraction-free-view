<?php
/**
 * Reader Mode unit tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use PHPUnit\Framework\TestCase;
use WPDFV\Admin\Upgrades;
use WPDFV\Includes\Actions;
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
		$this->assertSame( '', $defaults['custom_css'] );
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
				'custom_css'                  => "<style>\n.wpdfv-reader-modal { color: red; }\n</style>",
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
		$this->assertSame( '.wpdfv-reader-modal { color: red; }', $settings['custom_css'] );
	}

	/**
	 * Reader Mode custom CSS strips wrappers, invalid control characters, and large payloads.
	 *
	 * @return void
	 */
	public function test_sanitize_custom_css() {
		$css = "<style id=\"reader-css\">\n.wpdfv-reader-modal {\n\tcolor: red;\x00\x07\n}\n</style>";

		$this->assertSame(
			".wpdfv-reader-modal {\n\tcolor: red;\n}",
			Reader::sanitize_custom_css( $css )
		);

		$this->assertSame(
			Reader::CUSTOM_CSS_MAX_LENGTH,
			strlen( Reader::sanitize_custom_css( str_repeat( 'a', Reader::CUSTOM_CSS_MAX_LENGTH + 10 ) ) )
		);
	}

	/**
	 * Reader settings and public post type lookups are cached per request.
	 *
	 * @return void
	 */
	public function test_reader_settings_are_cached_per_request() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'where_to_display' => [ 'post', 'book' ],
				]
			),
			false
		);

		$this->assertSame( [ 'post', 'book' ], Reader::get_settings()['where_to_display'] );
		$this->assertSame( [ 'post', 'book' ], Reader::where_to_display() );
		$this->assertTrue( Reader::is_post_type_enabled( 'book' ) );
		$this->assertSame( 1, $GLOBALS['wpdfv_test_get_post_types_calls'] );
	}

	/**
	 * Settings update responses invalidate the cached normalized settings.
	 *
	 * @return void
	 */
	public function test_settings_update_invalidates_reader_settings_cache() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'button_text' => 'Before cache',
				]
			),
			false
		);

		$this->assertSame( 'Before cache', Reader::get_settings()['button_text'] );

		$request = new \WP_REST_Request();
		$request->set_param( 'button_text', 'After cache' );

		( new TestableSettingsApi() )->update_settings_response( $request );

		$this->assertSame( 'After cache', Reader::get_settings()['button_text'] );
	}

	/**
	 * Template catalogs and options are cached per request.
	 *
	 * @return void
	 */
	public function test_template_options_are_cached_per_request() {
		$template_filter_calls = 0;

		\add_filter(
			'wpdfv_modal_templates',
			static function ( $templates ) use ( &$template_filter_calls ) {
				++$template_filter_calls;
				$templates['compact'] = [
					'label'       => 'Compact',
					'description' => 'Compact layout',
					'content'     => '<!-- wp:post-title /-->',
				];

				return $templates;
			}
		);

		$this->assertContains( 'compact', array_column( Templates::get_template_options(), 'value' ) );
		$this->assertContains( 'compact', array_column( Templates::get_template_options(), 'value' ) );
		$this->assertSame( 1, $template_filter_calls );
	}

	/**
	 * Settings responses hide custom CSS from users without the CSS editing capability.
	 *
	 * @return void
	 */
	public function test_settings_response_hides_custom_css_without_capability() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'custom_css' => '.wpdfv-reader-modal { color: red; }',
				]
			),
			false
		);

		$GLOBALS['wpdfv_test_user_caps']['edit_css'] = false;

		$response = ( new TestableSettingsApi() )->get_settings_response();
		$data     = $response->get_data();

		$this->assertFalse( $data['canEditCustomCss'] );
		$this->assertSame( '', $data['settings']['custom_css'] );
	}

	/**
	 * Users without edit_css can save other settings without clearing existing custom CSS.
	 *
	 * @return void
	 */
	public function test_settings_update_preserves_custom_css_without_capability() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'custom_css' => '.wpdfv-reader-modal { color: red; }',
				]
			),
			false
		);

		$GLOBALS['wpdfv_test_user_caps']['edit_css'] = false;

		$request = new \WP_REST_Request();
		$request->set_param( 'button_text', 'Focus' );
		$request->set_param( 'custom_css', '.wpdfv-reader-modal { color: blue; }' );

		( new TestableSettingsApi() )->update_settings_response( $request );

		$settings = \get_option( 'wpdfv_settings' );

		$this->assertSame( 'Focus', $settings['button_text'] );
		$this->assertSame( '.wpdfv-reader-modal { color: red; }', $settings['custom_css'] );
	}

	/**
	 * Users with edit_css can save sanitized custom CSS.
	 *
	 * @return void
	 */
	public function test_settings_update_saves_custom_css_with_capability() {
		$GLOBALS['wpdfv_test_user_caps']['edit_css'] = true;

		$request = new \WP_REST_Request();
		$request->set_param( 'custom_css', '<style>.wpdfv-reader-modal { color: blue; }</style>' );

		( new TestableSettingsApi() )->update_settings_response( $request );

		$settings = \get_option( 'wpdfv_settings' );

		$this->assertSame( '.wpdfv-reader-modal { color: blue; }', $settings['custom_css'] );
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
		$this->assertSame( 1, Reader::calculate_reading_time( 'Intro [gallery ids="1,2,3"] outro.' ) );
		$this->assertSame( 1, Reader::calculate_reading_time( '<!-- wp:paragraph --><p>Rendered block text.</p><!-- /wp:paragraph -->' ) );
		$this->assertSame( 3, Reader::calculate_reading_time( str_repeat( '読', 401 ) ) );
	}

	/**
	 * Reading time filters remain backward compatible and can override counts.
	 *
	 * @return void
	 */
	public function test_calculate_reading_time_filters() {
		\add_filter(
			'wpdfv_reading_time_words_per_minute',
			static function () {
				return 100;
			}
		);

		$this->assertSame( 3, Reader::calculate_reading_time( str_repeat( 'word ', 201 ) ) );

		\add_filter(
			'wpdfv_reading_time_word_count',
			static function ( $words, $content ) {
				return false !== strpos( $content, 'override' ) ? 450 : $words;
			},
			10,
			2
		);

		$this->assertSame( 5, Reader::calculate_reading_time( 'override' ) );
	}

	/**
	 * Reader content sanitization removes executable blocks with their contents.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_removes_script_like_blocks() {
		$content = '<p>Start</p><script>window.option_df_3751 = {"outline":[]};</script><style>.reader{color:red;}</style><noscript>Enable JavaScript</noscript><p>End</p>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( '<p>Start</p>', $result );
		$this->assertStringContainsString( '<p>End</p>', $result );
		$this->assertStringNotContainsString( 'window.option_df_3751', $result );
		$this->assertStringNotContainsString( '.reader{color:red;}', $result );
		$this->assertStringNotContainsString( 'Enable JavaScript', $result );
		$this->assertStringNotContainsString( '<script', $result );
	}

	/**
	 * Escaped JavaScript examples in visible code samples remain readable.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_preserves_escaped_code_samples() {
		$content = '<pre><code>&lt;script&gt;window.option_df_3751 = {};&lt;/script&gt;</code></pre>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( '&lt;script&gt;window.option_df_3751 = {};&lt;/script&gt;', $result );
		$this->assertStringContainsString( '<pre><code>', $result );
	}

	/**
	 * Escaped executable blocks outside code samples should not print as text.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_removes_escaped_script_blocks_outside_code_samples() {
		$content = '<p>Start</p><div>&lt;script class="df-shortcode-script" type="application/javascript"&gt;window.option_df_3751 = {"outline":[]}; if(window.DFLIP &amp;&amp; window.DFLIP.parseBooks){ window.DFLIP.parseBooks(); }&lt;/script&gt;</div><pre><code>&lt;script&gt;window.option_df_visible = {};&lt;/script&gt;</code></pre><p>End</p>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( '<p>Start</p>', $result );
		$this->assertStringContainsString( '<p>End</p>', $result );
		$this->assertStringNotContainsString( 'window.option_df_3751', $result );
		$this->assertStringNotContainsString( 'DFLIP.parseBooks', $result );
		$this->assertStringContainsString( 'window.option_df_visible', $result );
	}

	/**
	 * Script bodies that already became standalone text should be removed.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_removes_standalone_flipbook_script_residue() {
		$content = '<div class="entry-content"><p>Before script content.</p><p>window.option_df_3751 = {"outline":[],"autoEnableOutline":"false"};</p><div>if(window.DFLIP &amp;&amp; window.DFLIP.parseBooks){ window.DFLIP.parseBooks(); }</div><p>After script content.</p></div>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( '<p>Before script content.</p>', $result );
		$this->assertStringContainsString( '<p>After script content.</p>', $result );
		$this->assertStringNotContainsString( 'window.option_df_3751', $result );
		$this->assertStringNotContainsString( 'autoEnableOutline', $result );
		$this->assertStringNotContainsString( 'DFLIP.parseBooks', $result );
	}

	/**
	 * Prepared Reader Mode content returns shortcode scripts separately.
	 *
	 * @return void
	 */
	public function test_prepare_rendered_content_extracts_scripts_for_modal_execution() {
		$content  = '<article><p>Readable content.</p><script id="df-shortcode-script" class="df-shortcode-script" type="application/javascript" data-book="3751">window.option_df_3751 = {"outline":[]}; window.wpdfvScriptRan = true;</script></article>';
		$prepared = Reader::prepare_rendered_content( $content );

		$this->assertStringContainsString( 'Readable content.', $prepared['content'] );
		$this->assertStringNotContainsString( '<script', $prepared['content'] );
		$this->assertStringNotContainsString( 'window.option_df_3751', $prepared['content'] );
		$this->assertCount( 1, $prepared['scripts'] );
		$this->assertSame( 'df-shortcode-script', $prepared['scripts'][0]['attributes']['id'] );
		$this->assertSame( 'df-shortcode-script', $prepared['scripts'][0]['attributes']['class'] );
		$this->assertSame( 'application/javascript', $prepared['scripts'][0]['attributes']['type'] );
		$this->assertSame( '3751', $prepared['scripts'][0]['attributes']['data-book'] );
		$this->assertStringContainsString( 'window.wpdfvScriptRan = true;', $prepared['scripts'][0]['content'] );
	}

	/**
	 * Developers can customize which full element blocks are removed.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_allows_custom_strip_tags() {
		\add_filter(
			'wpdfv_modal_content_strip_tags',
			static function ( $tags ) {
				$tags[] = 'template';

				return $tags;
			}
		);

		$result = Reader::sanitize_rendered_content( '<p>Visible</p><template>Hidden template data</template>' );

		$this->assertStringContainsString( 'Visible', $result );
		$this->assertStringNotContainsString( 'Hidden template data', $result );
	}

	/**
	 * REST content responses return sanitized Reader Mode content.
	 *
	 * @return void
	 */
	public function test_reader_content_response_sanitizes_rendered_content() {
		\update_option( 'wpdfv_settings', Reader::get_default_settings(), false );

		$post               = new \WP_Post();
		$post->ID           = 42;
		$post->post_type    = 'post';
		$post->post_content = 'Readable content.';

		$GLOBALS['wpdfv_test_posts'][42] = $post;

		\add_filter(
			'wpdfv_modal_template_content',
			static function () {
				return '<article><p>Readable content.</p><script>window.option_df_3751 = {"outline":[]};</script></article>';
			}
		);

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 42 );

		$response = ( new Main() )->get_content_response( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( 'Readable content.', $data['content'] );
		$this->assertSame( 'https://example.com/?p=42', $data['permalink'] );
		$this->assertSame( 1, $data['readingTime']['minutes'] );
		$this->assertStringNotContainsString( 'window.option_df_3751', $data['content'] );
		$this->assertArrayHasKey( 'scripts', $data );
		$this->assertCount( 1, $data['scripts'] );
		$this->assertStringContainsString( 'window.option_df_3751', $data['scripts'][0]['content'] );
	}

	/**
	 * REST content reading time uses rendered Reader content without another render pass.
	 *
	 * @return void
	 */
	public function test_reader_content_response_uses_rendered_content_for_reading_time() {
		\update_option( 'wpdfv_settings', Reader::get_default_settings(), false );

		$post               = new \WP_Post();
		$post->ID           = 43;
		$post->post_type    = 'post';
		$post->post_content = 'Short source.';

		$GLOBALS['wpdfv_test_posts'][43] = $post;

		\add_filter(
			'wpdfv_modal_template_content',
			static function () {
				return '<article><p>' . str_repeat( 'rendered ', 401 ) . '</p></article>';
			}
		);

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 43 );

		$response = ( new Main() )->get_content_response( $request );
		$data     = $response->get_data();

		$this->assertSame( 3, $data['readingTime']['minutes'] );
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
	 * Development-only 2.x markers are normalized to the current release path.
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

		$this->assertSame( WPDFV_VERSION, \get_option( 'wpdfv_version' ) );
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
	 * Frontend Reader Mode assets should not load the admin components package.
	 *
	 * @return void
	 */
	public function test_frontend_reader_asset_omits_wp_components_dependency() {
		$asset_file = WPDFV_PLUGIN_DIR . 'assets/dist/js/wpdfv.asset.php';

		$this->assertFileExists( $asset_file );

		$asset = require $asset_file;

		$this->assertIsArray( $asset );
		$this->assertArrayHasKey( 'dependencies', $asset );
		$this->assertContains( 'wp-api-fetch', $asset['dependencies'] );
		$this->assertContains( 'wp-element', $asset['dependencies'] );
		$this->assertNotContains( 'wp-components', $asset['dependencies'] );
	}

	/**
	 * Visitor enqueue keeps wp-components styles off frontend pages.
	 *
	 * @return void
	 */
	public function test_frontend_enqueue_does_not_enqueue_wp_components_style() {
		Actions::enqueue_frontend_assets();

		$this->assertContains( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['styles'] );
		$this->assertContains( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['scripts'] );
		$this->assertNotContains( 'wp-components', $GLOBALS['wpdfv_test_enqueued']['styles'] );
	}

	/**
	 * Frontend custom CSS is attached to the Reader Mode stylesheet, not frontend settings JSON.
	 *
	 * @return void
	 */
	public function test_frontend_enqueue_adds_custom_css_inline_style() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'custom_css' => '.wpdfv-reader-modal { color: red; }',
				]
			),
			false
		);

		Actions::enqueue_frontend_assets();

		$this->assertSame(
			[ '.wpdfv-reader-modal { color: red; }' ],
			$GLOBALS['wpdfv_test_enqueued']['inline_styles']['wpdfv-core']
		);
		$this->assertStringNotContainsString(
			'custom_css',
			$GLOBALS['wpdfv_test_enqueued']['inline']['wpdfv-core'][0]
		);
		$this->assertStringNotContainsString(
			'.wpdfv-reader-modal',
			$GLOBALS['wpdfv_test_enqueued']['inline']['wpdfv-core'][0]
		);
	}

	/**
	 * Developers can filter Reader Mode custom CSS before frontend output.
	 *
	 * @return void
	 */
	public function test_frontend_custom_css_filter_can_disable_output() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'custom_css' => '.wpdfv-reader-modal { color: red; }',
				]
			),
			false
		);

		\add_filter(
			'wpdfv_custom_css',
			static function () {
				return '';
			}
		);

		Actions::enqueue_frontend_assets();

		$this->assertArrayNotHasKey( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['inline_styles'] );
	}

	/**
	 * Manual shortcode output should enqueue the shared frontend assets.
	 *
	 * @return void
	 */
	public function test_shortcode_uses_shared_frontend_assets() {
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
		$post->post_type = 'post';

		$GLOBALS['wpdfv_test_posts'][42] = $post;

		$shortcode = new Main();
		$markup    = $shortcode->render_shortcode( [ 'post_id' => 42 ] );

		$this->assertStringContainsString( 'data-post-id="42"', $markup );
		$this->assertContains( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['scripts'] );
		$this->assertArrayHasKey( 'wpdfv-core', $GLOBALS['wpdfv_test_enqueued']['inline'] );
		$this->assertCount( 1, $GLOBALS['wpdfv_test_enqueued']['inline']['wpdfv-core'] );
	}

	/**
	 * Multiple reader placements should not duplicate inline runtime settings.
	 *
	 * @return void
	 */
	public function test_reader_assets_add_inline_settings_once_for_multiple_placements() {
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
		$post->post_type = 'post';

		$GLOBALS['wpdfv_test_posts'][42] = $post;

		$block = (object) [
			'context' => [
				'postId'   => 42,
				'postType' => 'post',
			],
		];

		$blocks    = new Blocks();
		$shortcode = new Main();

		$blocks->render_reader_button( [], '', $block );
		$shortcode->render_shortcode( [ 'post_id' => 42 ] );
		Actions::enqueue_frontend_assets();

		$this->assertCount( 1, $GLOBALS['wpdfv_test_enqueued']['inline']['wpdfv-core'] );
	}

	/**
	 * Built block editor assets must declare every WordPress package they use.
	 *
	 * @return void
	 */
	public function test_reader_block_editor_asset_declares_runtime_dependencies() {
		$asset_file = WPDFV_PLUGIN_DIR . 'assets/dist/js/wpdfv-block.asset.php';

		$this->assertFileExists( $asset_file );

		$asset = require $asset_file;

		$this->assertIsArray( $asset );
		$this->assertArrayHasKey( 'dependencies', $asset );

		foreach ( [ 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n' ] as $dependency ) {
			$this->assertContains( $dependency, $asset['dependencies'] );
		}
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
		$this->assertSame( 1, $GLOBALS['wpdfv_test_get_plugins_calls'] );
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
		$this->assertSame( 2, $GLOBALS['wpdfv_test_get_plugins_calls'] );
	}
}
