<?php
/**
 * Built asset regression tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for selectors that are shared with WordPress editor components.
 */
class AssetsTest extends TestCase {
	/**
	 * Reader Mode component selectors must not style unrelated editor controls.
	 *
	 * @return void
	 */
	public function test_reader_component_selectors_are_scoped_to_reader_root() {
		$unscoped_selectors = [
			'/(?:^|})\\.components-modal__screen-overlay(?:\\{|,)/',
			'/(?:^|})\\.components-button(?:[\\s:{.#,]|$)/',
			'/(?:^|})\\.components-button-group(?:[\\s:{.#,]|$)/',
			'/(?:^|})\\.components-modal__header(?:[\\s:{.#,]|$)/',
		];

		foreach ( [ 'wpdfv.css', 'wpdfv-rtl.css' ] as $stylesheet ) {
			$path = WPDFV_PLUGIN_DIR . "assets/dist/{$stylesheet}";
			$css  = file_get_contents( $path );

			$this->assertFileExists( $path );
			$this->assertIsString( $css );
			$this->assertStringContainsString( ':where(#wpdfv-reader-root) .components-button{', $css );
			$this->assertStringContainsString( ':where(#wpdfv-reader-root) .components-modal__screen-overlay{', $css );
			$this->assertStringContainsString( 'body.wpdfv-reader-mode-active>:not(#wpdfv-reader-root)', $css );

			foreach ( $unscoped_selectors as $selector ) {
				$this->assertDoesNotMatchRegularExpression( $selector, $css, "Unscoped selector found in {$stylesheet}." );
			}
		}
	}
}
