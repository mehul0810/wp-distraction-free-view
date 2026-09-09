<?php
/**
 * WordPress integration test bootstrap.
 *
 * @package WPDistractionFreeView
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test suite not found. Run: bash bin/install-wp-tests.sh wordpress_test root root localhost latest\n" );
	exit( 1 );
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/wp-distraction-free-view.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
