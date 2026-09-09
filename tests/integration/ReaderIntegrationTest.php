<?php
/**
 * Reader Mode WordPress integration tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Server;
use WPDFV\Admin\Upgrades;
use WPDFV\Includes\Reader;
use WPDFV\Plugin;

/**
 * Covers WordPress contracts that the fast shim tests cannot prove.
 */
class ReaderIntegrationTest extends TestCase {
	/**
	 * REST server instance before the test.
	 *
	 * @var WP_REST_Server|null
	 */
	private $server;

	/**
	 * Post IDs created by the test.
	 *
	 * @var int[]
	 */
	private $post_ids = [];

	/**
	 * Site IDs created by the test.
	 *
	 * @var int[]
	 */
	private $site_ids = [];

	/**
	 * Set up WordPress REST routing for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->server              = isset( $GLOBALS['wp_rest_server'] ) ? $GLOBALS['wp_rest_server'] : null;
		$GLOBALS['wp_rest_server'] = new WP_REST_Server();

		do_action( 'rest_api_init' );
		update_option( 'wpdfv_settings', Reader::get_default_settings(), false );
		wp_set_current_user( 0 );
	}

	/**
	 * Restore REST server state.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ( $this->post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		foreach ( $this->site_ids as $site_id ) {
			wp_delete_site( $site_id );
		}

		delete_option( 'wpdfv_settings' );
		delete_option( 'wpdfv_version' );
		delete_option( 'wpdfv_general' );
		delete_option( 'wpdfv_upgrade_error' );
		$GLOBALS['wp_rest_server'] = $this->server;

		parent::tearDown();
	}

	/**
	 * Missing posts return the public REST not-found contract.
	 *
	 * @return void
	 */
	public function test_rest_content_returns_404_for_missing_post() {
		$response = $this->dispatch_content_request( 999999 );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'wpdfv_post_not_found', $response->as_error()->get_error_code() );
	}

	/**
	 * Password-protected posts are not exposed through Reader Mode.
	 *
	 * @return void
	 */
	public function test_rest_content_returns_403_for_password_protected_post() {
		$post_id = $this->create_post(
			[
				'post_status'   => 'publish',
				'post_password' => 'secret',
			]
		);

		$response = $this->dispatch_content_request( $post_id );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'wpdfv_post_forbidden', $response->as_error()->get_error_code() );
	}

	/**
	 * Private posts are forbidden to anonymous readers.
	 *
	 * @return void
	 */
	public function test_rest_content_returns_403_for_private_post() {
		$post_id = $this->create_post(
			[
				'post_status' => 'private',
			]
		);

		$response = $this->dispatch_content_request( $post_id );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'wpdfv_post_forbidden', $response->as_error()->get_error_code() );
	}

	/**
	 * Disabled post types are not available through Reader Mode.
	 *
	 * @return void
	 */
	public function test_rest_content_returns_403_for_disabled_post_type() {
		update_option(
			'wpdfv_settings',
			array_merge(
				Reader::get_default_settings(),
				[
					'where_to_display' => [ 'page' ],
				]
			),
			false
		);

		$post_id = $this->create_post(
			[
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		$response = $this->dispatch_content_request( $post_id );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'wpdfv_post_forbidden', $response->as_error()->get_error_code() );
	}

	/**
	 * Public posts return rendered Reader Mode content.
	 *
	 * @return void
	 */
	public function test_rest_content_returns_200_for_public_post() {
		$post_id = $this->create_post(
			[
				'post_content' => '<p>Readable integration content.</p>',
				'post_status'  => 'publish',
				'post_title'   => 'Reader contract',
			]
		);

		$response = $this->dispatch_content_request( $post_id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $post_id, $data['id'] );
		$this->assertStringContainsString( 'Readable integration content.', $data['content'] );
		$this->assertArrayHasKey( 'readingTime', $data );
	}

	/**
	 * Real WordPress KSES keeps supported provider embeds represented in REST output.
	 *
	 * @return void
	 */
	public function test_rest_content_preserves_provider_fallbacks_after_kses() {
		$source_content = '<p>Embedded content.</p><figure class="wp-block-embed is-provider-youtube"><div class="wp-block-embed__wrapper"><iframe src="https://www.youtube.com/embed/video-123"></iframe></div></figure><figure class="wp-block-embed is-provider-spotify"><div class="wp-block-embed__wrapper"><iframe src="https://open.spotify.com/embed/playlist/playlist-123"></iframe></div></figure>';
		$previous_user  = get_current_user_id();

		wp_set_current_user( 1 );

		try {
			$post_id = $this->create_post(
				[
					'post_content' => $source_content,
					'post_status'  => 'publish',
				]
			);
		} finally {
			wp_set_current_user( $previous_user );
		}

		$response = $this->dispatch_content_request( $post_id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringContainsString( 'Open YouTube content', $data['content'] );
		$this->assertStringContainsString( 'Open Spotify content', $data['content'] );
		$this->assertStringNotContainsString( '<iframe', $data['content'] );
		$this->assertSame( [], $data['scripts'] );
		$this->assertSame( $source_content, get_post( $post_id )->post_content );
	}

	/**
	 * The reader button block registers from block.json and renders from post context.
	 *
	 * @return void
	 */
	public function test_reader_button_block_registers_and_renders() {
		$registry = \WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'wpdfv/reader-button' );

		$this->assertNotFalse( $block );
		$this->assertSame( 'Reader Mode Toggle', $block->title );
		$this->assertContains( 'postId', $block->uses_context );

		$post_id = $this->create_post(
			[
				'post_status' => 'publish',
			]
		);

		$markup = do_blocks(
			sprintf(
				'<!-- wp:query {"queryId":1,"query":{"perPage":1,"postType":"post","include":[%1$d]}} --><!-- wp:post-template --><!-- wp:wpdfv/reader-button {"buttonText":"Open Reader"} /--><!-- /wp:post-template --><!-- /wp:query -->',
				$post_id
			)
		);

		$this->assertStringContainsString( 'wpdfv-reader-toggle', $markup );
		$this->assertStringContainsString( 'data-post-id="' . $post_id . '"', $markup );
		$this->assertStringContainsString( 'Open Reader', $markup );
	}

	/**
	 * Activation initializes fresh installs using real WordPress options.
	 *
	 * @return void
	 */
	public function test_activation_initializes_default_options() {
		delete_option( 'wpdfv_settings' );
		delete_option( 'wpdfv_version' );
		delete_option( 'wpdfv_general' );

		( new Plugin() )->activate();

		$this->assertSame( WPDFV_VERSION, get_option( 'wpdfv_version' ) );
		$this->assertSame( Reader::get_default_settings(), get_option( 'wpdfv_settings' ) );
	}

	/**
	 * Legacy settings migrate through WordPress option APIs.
	 *
	 * @return void
	 */
	public function test_upgrade_migrates_legacy_options() {
		update_option( 'wpdfv_version', '1.5.0', false );
		update_option(
			'wpdfv_general',
			[
				'display_read_mode_at' => 'before_content',
				'read_mode_btn_text'   => 'Legacy button',
			],
			false
		);

		( new Upgrades() )->process_automatic_upgrades();

		$settings = get_option( 'wpdfv_settings' );

		$this->assertSame( WPDFV_VERSION, get_option( 'wpdfv_version' ) );
		$this->assertSame( 'before_content', $settings['display_location'] );
		$this->assertSame( 'Legacy button', $settings['button_text'] );
	}

	/**
	 * Network activation initializes each site when the suite runs in multisite mode.
	 *
	 * @group ms-required
	 *
	 * @return void
	 */
	public function test_network_activation_initializes_each_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Run integration tests with WP_MULTISITE=1 to cover network activation.' );
		}

		$site_id = $this->create_site();
		delete_option( 'wpdfv_settings' );
		delete_option( 'wpdfv_version' );

		switch_to_blog( $site_id );
		delete_option( 'wpdfv_settings' );
		delete_option( 'wpdfv_version' );
		restore_current_blog();

		( new Plugin() )->activate( true );

		$this->assertSame( WPDFV_VERSION, get_option( 'wpdfv_version' ) );

		switch_to_blog( $site_id );
		$this->assertSame( WPDFV_VERSION, get_option( 'wpdfv_version' ) );
		$this->assertSame( Reader::get_default_settings(), get_option( 'wpdfv_settings' ) );
		restore_current_blog();
	}

	/**
	 * Dispatch a Reader Mode content REST request.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch_content_request( $post_id ) {
		$request = new WP_REST_Request( 'GET', '/wp-distraction-free-view/v1/content/' . absint( $post_id ) );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Create a WordPress post and track it for cleanup.
	 *
	 * @param array $args Post arguments.
	 *
	 * @return int
	 */
	private function create_post( array $args = [] ) {
		$post_id = wp_insert_post(
			array_merge(
				[
					'post_author'  => 1,
					'post_content' => 'Reader content.',
					'post_status'  => 'publish',
					'post_title'   => 'Reader test post',
					'post_type'    => 'post',
				],
				$args
			),
			true
		);

		$this->assertNotWPError( $post_id );
		$this->post_ids[] = $post_id;

		return $post_id;
	}

	/**
	 * Create a multisite blog and track it for cleanup.
	 *
	 * @return int
	 */
	private function create_site() {
		$site_id = wp_insert_site(
			[
				'domain' => 'site' . wp_rand( 1000, 9999 ) . '.example.org',
				'path'   => '/',
			]
		);

		$this->assertNotWPError( $site_id );
		$this->site_ids[] = $site_id;

		return $site_id;
	}

	/**
	 * Assert a value is not a WP_Error instance.
	 *
	 * @param mixed $actual Value to inspect.
	 *
	 * @return void
	 */
	private function assertNotWPError( $actual ) {
		$this->assertFalse(
			is_wp_error( $actual ),
			is_wp_error( $actual ) ? $actual->get_error_message() : 'Value is not a WP_Error.'
		);
	}
}
