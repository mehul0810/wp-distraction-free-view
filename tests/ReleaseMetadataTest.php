<?php
/**
 * Release metadata synchronization tests.
 *
 * @package WPDistractionFreeView
 */

namespace WPDFV\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Ensures packaged release identifiers remain synchronized.
 */
class ReleaseMetadataTest extends TestCase {
	/**
	 * All release metadata files use the same version.
	 *
	 * @return void
	 */
	public function test_release_metadata_versions_are_synchronized() {
		$root = dirname( __DIR__ );

		$plugin_header = $this->read_file( $root . '/wp-distraction-free-view.php' );
		$constants     = $this->read_file( $root . '/config/constants.php' );
		$package       = json_decode( $this->read_file( $root . '/package.json' ), true, 512, JSON_THROW_ON_ERROR );
		$package_lock  = json_decode( $this->read_file( $root . '/package-lock.json' ), true, 512, JSON_THROW_ON_ERROR );
		$readme        = $this->read_file( $root . '/readme.txt' );
		$pot           = $this->read_file( $root . '/languages/wpdfv.pot' );

		preg_match( '/^\s*\* Version:\s*(\S+)/m', $plugin_header, $plugin_match );
		preg_match( "/define\(\s*'WPDFV_VERSION',\s*'([^']+)'/", $constants, $constant_match );
		preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $stable_tag_match );
		preg_match( '/Project-Id-Version: WP Distraction Free View ([^"\\\\]+)/', $pot, $pot_match );

		$versions = [
			'plugin header' => $plugin_match[1] ?? null,
			'constant'      => $constant_match[1] ?? null,
			'package'       => $package['version'] ?? null,
			'package lock'  => $package_lock['version'] ?? null,
			'lock root'     => $package_lock['packages']['']['version'] ?? null,
			'stable tag'    => $stable_tag_match[1] ?? null,
			'pot'           => $pot_match[1] ?? null,
		];

		$this->assertSame( '1.8.2', $versions['plugin header'] );
		$this->assertSame( 1, count( array_unique( $versions ) ), print_r( $versions, true ) );
	}

	/**
	 * Read a required release metadata file.
	 *
	 * @param string $path File path.
	 * @return string
	 */
	private function read_file( $path ) {
		$contents = file_get_contents( $path );
		$this->assertIsString( $contents );

		return $contents;
	}
}
