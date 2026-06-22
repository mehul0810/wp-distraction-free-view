<?php
/**
 * Reader Mode WordPress integration tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests\Integration;

use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;
use WPDFV\Admin\Upgrades;
use WPDFV\Includes\Reader;
use WPDFV\Plugin;

/**
 * Covers WordPress contracts that the fast shim tests cannot prove.
 */
class ReaderIntegrationTest extends WP_UnitTestCase {
	/**
	 * REST server instance before the test.
	 *
	 * @var WP_REST_Server|null
	 */
	private $server;

	/**
	 * Set up WordPress REST routing for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

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
	public function tear_down() {
		$GLOBALS['wp_rest_server'] = $this->server;

		parent::tear_down();
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
		$post_id = self::factory()->post->create(
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
		$post_id = self::factory()->post->create(
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

		$post_id = self::factory()->post->create(
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
		$post_id = self::factory()->post->create(
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

		$post_id = self::factory()->post->create(
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

		$site_id = self::factory()->blog->create();
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
}
