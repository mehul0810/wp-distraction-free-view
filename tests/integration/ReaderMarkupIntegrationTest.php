<?php
/**
 * WordPress HTML handling tests for Reader Mode.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WPDFV\Includes\Reader;

/**
 * Covers Reader markup with WordPress sanitization and attribute parsing.
 */
class ReaderMarkupIntegrationTest extends TestCase {

	/**
	 * Real KSES preserves the paragraph between comments with apostrophes.
	 *
	 * @return void
	 */
	public function test_reader_keeps_content_between_html_comments() {
		$content  = "<!-- Editor's note --><p>Visible paragraph.</p><!-- Editor's end note -->";
		$prepared = Reader::prepare_rendered_content( $content );
		$document = new \DOMDocument();
		$document->loadHTML( $prepared['content'] );

		$this->assertCount( 1, $document->getElementsByTagName( 'p' ) );
		$this->assertSame( 'Visible paragraph.', $document->getElementsByTagName( 'p' )->item( 0 )->textContent );
	}

	/**
	 * Only executable-context scripts are returned for Reader replay.
	 *
	 * @return void
	 */
	public function test_reader_preserves_script_context_and_heading_ids() {
		$content  = '<!-- <script>comment marker</script> -->'
			. '<textarea><script>textarea marker</script></textarea>'
			. '<template><script>template marker</script></template>'
			. '<p id="introduction">Start</p><h2 title="a > b">Introduction</h2>'
			. '<script data-example="a > b">active marker</script>';
		$prepared = Reader::prepare_rendered_content( $content );

		$this->assertCount( 1, $prepared['scripts'] );
		$this->assertSame( 'active marker', $prepared['scripts'][0]['content'] );
		$this->assertSame( 'a > b', $prepared['scripts'][0]['attributes']['data-example'] );
		$this->assertSame( 'introduction-2', $prepared['toc'][0]['id'] );
		$this->assertStringContainsString( 'title="a &gt; b"', $prepared['content'] );
	}
}
