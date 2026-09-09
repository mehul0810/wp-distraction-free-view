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
		$this->assertFalse( $defaults['reader_toc_enabled'] );
		$this->assertFalse( $defaults['reader_resume_enabled'] );
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
				'reader_toc_enabled'          => 1,
				'reader_resume_enabled'       => 1,
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
		$this->assertTrue( $settings['reader_toc_enabled'] );
		$this->assertTrue( $settings['reader_resume_enabled'] );
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
	 * Reader content wrappers with mixed article text should not be stripped.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_preserves_mixed_reader_content_wrappers() {
		$content = '<div class="wp-block-post-content"><p>First readable paragraph.</p>window.option_df_3751 = {"outline":[],"autoEnableOutline":"false"};<p>Second readable paragraph.</p></div>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( '<div class="wp-block-post-content">', $result );
		$this->assertStringContainsString( '<p>First readable paragraph.</p>', $result );
		$this->assertStringContainsString( '<p>Second readable paragraph.</p>', $result );
		$this->assertStringNotContainsString( 'window.option_df_3751', $result );
		$this->assertStringNotContainsString( 'autoEnableOutline', $result );
	}

	/**
	 * Reader Mode strips its own launch control markup from rendered content.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_strips_reader_toggle_markup() {
		$content = '<article><p>Before.</p><div class="wpdfv-fullscreen-container"><button type="button" class="wpdfv-fullscreen-btn wpdfv-reader-toggle" data-post-id="7" aria-haspopup="dialog" aria-label="Read in Reader Mode">Read in Reader Mode</button></div><p>After.</p></article>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( '<p>Before.</p>', $result );
		$this->assertStringContainsString( '<p>After.</p>', $result );
		$this->assertStringNotContainsString( 'wpdfv-reader-toggle', $result );
		$this->assertStringNotContainsString( 'Read in Reader Mode', $result );
	}

	/**
	 * Supported provider iframes remain represented without allowing iframe markup.
	 *
	 * @return void
	 */
	public function test_sanitize_rendered_content_preserves_supported_provider_fallback_links() {
		$content = '<figure class="wp-block-embed is-provider-youtube"><div class="wp-block-embed__wrapper"><iframe src="https://www.youtube.com/embed/video-123" title="YouTube video"></iframe></div></figure>' .
			'<figure class="wp-block-embed is-provider-spotify"><div class="wp-block-embed__wrapper"><iframe src="https://open.spotify.com/embed/playlist/playlist-123" title="Spotify playlist"></iframe></div></figure>' .
			'<iframe src="https://player.example.com/embed/ignored"></iframe>' .
			'<iframe src="javascript://www.youtube.com/embed/ignored"></iframe>';
		$result  = Reader::sanitize_rendered_content( $content );

		$this->assertStringContainsString( 'class="wpdfv-provider-embed-fallback"', $result );
		$this->assertStringContainsString( 'href="https://www.youtube.com/embed/video-123"', $result );
		$this->assertStringContainsString( 'Open YouTube content', $result );
		$this->assertStringContainsString( 'href="https://open.spotify.com/embed/playlist/playlist-123"', $result );
		$this->assertStringContainsString( 'Open Spotify content', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $result );
		$this->assertStringContainsString( 'aria-label="Open YouTube content"', $result );
		$this->assertStringNotContainsString( '<iframe', $result );
		$this->assertStringNotContainsString( 'player.example.com', $result );
		$this->assertStringNotContainsString( 'javascript://www.youtube.com', $result );
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
	 * RTL and Unicode-heavy content remains intact after Reader Mode preparation.
	 *
	 * @return void
	 */
	public function test_prepare_rendered_content_preserves_rtl_unicode_blocks() {
		$content  = '<article><h2>«حالت مطالعه» برای WPDFV 1.8.0</h2><p dir="rtl" lang="fa">می‌خواهیم اعداد ۱۲۳۴۵۶۷۸۹۰، اعداد عربی ١٢٣٤٥٦٧٨٩٠، و ایموجی 👩‍💻 را ببینیم.</p><p dir="rtl" lang="fa">عبارت دارای اِعراب: السَّلَامُ عَلَيْكُمْ.</p><p dir="auto">Mixed LTR/RTL with https://development.wp.local/?reader-mode=1 and <code>wpdfv_reader_preferences</code>.</p><figure class="wp-block-table"><table><tbody><tr><td>۱</td><td>۱۲٬۳۴۵</td></tr></tbody></table></figure><figure class="wp-block-image"><img src="https://example.com/wp-content/plugins/wp-distraction-free-view/assets/dist/images/wpdfv-icon.png" alt="WPDFV fixture icon" /></figure><div class="wp-caption"><img src="https://example.com/wp-content/plugins/wp-distraction-free-view/assets/dist/images/wpdfv-icon.png" alt="نماد Reader Mode" /><p class="wp-caption-text">نماد Reader Mode با caption فارسی</p></div></article>';
		$prepared = Reader::prepare_rendered_content( $content );

		$this->assertStringContainsString( 'می‌خواهیم', $prepared['content'] );
		$this->assertStringContainsString( '۱۲۳۴۵۶۷۸۹۰', $prepared['content'] );
		$this->assertStringContainsString( '١٢٣٤٥٦٧٨٩٠', $prepared['content'] );
		$this->assertStringContainsString( '👩‍💻', $prepared['content'] );
		$this->assertStringContainsString( 'السَّلَامُ عَلَيْكُمْ', $prepared['content'] );
		$this->assertStringContainsString( 'https://development.wp.local/?reader-mode=1', $prepared['content'] );
		$this->assertStringContainsString( 'wp-caption-text', $prepared['content'] );
		$this->assertStringContainsString( '<table>', $prepared['content'] );
		$this->assertStringContainsString( '<img src="https://example.com/wp-content/plugins/wp-distraction-free-view/assets/dist/images/wpdfv-icon.png"', $prepared['content'] );
		$this->assertNotEmpty( $prepared['toc'] );
		$this->assertSame( '«حالت مطالعه» برای WPDFV 1.8.0', $prepared['toc'][0]['text'] );
	}

	/**
	 * Reader Mode table of contents uses generated IDs for headings without IDs.
	 *
	 * @return void
	 */
	public function test_prepare_table_of_contents_adds_missing_heading_ids() {
		$prepared = Reader::prepare_table_of_contents( '<article><h2>First Section</h2><p>Text</p><h3>Nested Topic</h3></article>' );

		$this->assertSame(
			[
				[
					'id'    => 'first-section',
					'level' => 2,
					'text'  => 'First Section',
				],
				[
					'id'    => 'nested-topic',
					'level' => 3,
					'text'  => 'Nested Topic',
				],
			],
			$prepared['items']
		);
		$this->assertStringContainsString( '<h2 id="first-section">First Section</h2>', $prepared['content'] );
		$this->assertStringContainsString( '<h3 id="nested-topic">Nested Topic</h3>', $prepared['content'] );
	}

	/**
	 * Duplicate heading IDs are made unique only in rendered Reader Mode output.
	 *
	 * @return void
	 */
	public function test_prepare_table_of_contents_handles_duplicate_heading_ids() {
		$prepared = Reader::prepare_table_of_contents( '<h2 id="intro">Intro</h2><h2 id="intro">Intro again</h2><h2>Intro</h2>' );

		$this->assertSame( 'intro', $prepared['items'][0]['id'] );
		$this->assertSame( 'intro-2', $prepared['items'][1]['id'] );
		$this->assertSame( 'intro-3', $prepared['items'][2]['id'] );
		$this->assertStringContainsString( '<h2 id="intro">Intro</h2>', $prepared['content'] );
		$this->assertStringContainsString( '<h2 id="intro-2">Intro again</h2>', $prepared['content'] );
		$this->assertStringContainsString( '<h2 id="intro-3">Intro</h2>', $prepared['content'] );
	}

	/**
	 * Empty headings are ignored so the navigation only contains useful labels.
	 *
	 * @return void
	 */
	public function test_prepare_table_of_contents_ignores_empty_headings() {
		$prepared = Reader::prepare_table_of_contents( '<h2><span></span></h2><h2>Visible</h2>' );

		$this->assertCount( 1, $prepared['items'] );
		$this->assertSame( 'Visible', $prepared['items'][0]['text'] );
		$this->assertStringContainsString( '<h2><span></span></h2>', $prepared['content'] );
		$this->assertStringContainsString( '<h2 id="visible">Visible</h2>', $prepared['content'] );
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
		$this->assertArrayHasKey( 'toc', $data );
		$this->assertCount( 1, $data['scripts'] );
		$this->assertStringContainsString( 'window.option_df_3751', $data['scripts'][0]['content'] );
	}

	/**
	 * REST responses expose accessible provider fallbacks for supported embeds.
	 *
	 * @return void
	 */
	public function test_reader_content_response_preserves_provider_fallback_contract() {
		\update_option( 'wpdfv_settings', Reader::get_default_settings(), false );

		$post               = new \WP_Post();
		$post->ID           = 44;
		$post->post_type    = 'post';
		$post->post_content = 'Source content.';

		$GLOBALS['wpdfv_test_posts'][44] = $post;

		\add_filter(
			'wpdfv_modal_template_content',
			static function () {
				return '<article><p>Source content.</p><div class="wp-block-embed__wrapper"><iframe src="https://www.youtube.com/embed/video-123"></iframe><iframe src="https://open.spotify.com/embed/album/album-123"></iframe></div></article>';
			}
		);

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 44 );

		$response = ( new Main() )->get_content_response( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( 'Open YouTube content', $data['content'] );
		$this->assertStringContainsString( 'Open Spotify content', $data['content'] );
		$this->assertStringContainsString( 'href="https://www.youtube.com/embed/video-123"', $data['content'] );
		$this->assertStringContainsString( 'href="https://open.spotify.com/embed/album/album-123"', $data['content'] );
		$this->assertStringNotContainsString( '<iframe', $data['content'] );
		$this->assertSame( [], $data['scripts'] );
		$this->assertSame( 'Source content.', $post->post_content );
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
	 * Frontend and admin bundles must use the JSX runtime available to the plugin minimum.
	 *
	 * @return void
	 */
	public function test_reader_bundles_use_wordpress_6_0_compatible_jsx_runtime() {
		foreach ( [ 'wpdfv', 'wpdfv-admin' ] as $handle ) {
			$asset_file  = WPDFV_PLUGIN_DIR . "assets/dist/js/{$handle}.asset.php";
			$bundle_file = WPDFV_PLUGIN_DIR . "assets/dist/js/{$handle}.js";

			$this->assertFileExists( $asset_file );
			$this->assertFileExists( $bundle_file );

			$asset  = require $asset_file;
			$bundle = file_get_contents( $bundle_file );

			$this->assertIsArray( $asset );
			$this->assertArrayHasKey( 'dependencies', $asset );
			$this->assertNotContains( 'react-jsx-runtime', $asset['dependencies'] );
			$this->assertIsString( $bundle );
			$this->assertStringNotContainsString( 'ReactJSXRuntime', $bundle );
			$this->assertStringContainsString( 'createElement', $bundle );
		}
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
	 * Frontend settings expose only the resume feature flag and browser storage key.
	 *
	 * @return void
	 */
	public function test_frontend_enqueue_adds_reader_resume_runtime_settings() {
		\update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'reader_resume_enabled' => true,
				]
			),
			false
		);

		Actions::enqueue_frontend_assets();

		$inline_settings = $GLOBALS['wpdfv_test_enqueued']['inline']['wpdfv-core'][0];

		$this->assertStringContainsString( '"readerResumeEnabled":true', $inline_settings );
		$this->assertStringContainsString( '"positionsStorageKey":"wpdfv_reader_positions"', $inline_settings );
		$this->assertStringNotContainsString( 'scrollTop', $inline_settings );
		$this->assertStringNotContainsString( 'progress":', $inline_settings );
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
