<?php
/**
 * PHPUnit bootstrap for focused plugin unit tests.
 *
 * These tests exercise pure plugin behavior with a minimal WordPress shim so
 * they can run quickly in CI without provisioning a full WordPress install.
 *
 * @package WPDistractionFreeView
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$GLOBALS['wpdfv_test_options']    = [];
$GLOBALS['wpdfv_test_shortcodes'] = [];

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		/**
		 * Post ID.
		 *
		 * @var int
		 */
		public $ID = 0;

		/**
		 * Post type.
		 *
		 * @var string
		 */
		public $post_type = 'post';

		/**
		 * Post content.
		 *
		 * @var string
		 */
		public $post_content = '';
	}
}

function wpdfv_tests_reset_state() {
	$GLOBALS['wpdfv_test_options']    = [];
	$GLOBALS['wpdfv_test_shortcodes'] = [];
	$_GET                             = [];
}

function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}

function plugin_dir_url( $file ) {
	return 'https://example.org/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function trailingslashit( $value ) {
	return rtrim( $value, '/\\' ) . '/';
}

function get_option( $option, $default_value = false ) {
	return array_key_exists( $option, $GLOBALS['wpdfv_test_options'] ) ? $GLOBALS['wpdfv_test_options'][ $option ] : $default_value;
}

function update_option( $option, $value, $autoload = null ) {
	$GLOBALS['wpdfv_test_options'][ $option ] = $value;

	return true;
}

function delete_option( $option ) {
	unset( $GLOBALS['wpdfv_test_options'][ $option ] );

	return true;
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	return true;
}

function add_shortcode( $tag, $callback ) {
	$GLOBALS['wpdfv_test_shortcodes'][ $tag ] = $callback;

	return true;
}

function current_user_can( $capability ) {
	return true;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function _n( $single, $plural, $number, $domain = 'default' ) {
	return 1 === (int) $number ? $single : $plural;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return esc_html( $text );
}

function sanitize_key( $key ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
}

function sanitize_title( $title ) {
	return sanitize_key( str_replace( ' ', '-', (string) $title ) );
}

function sanitize_text_field( $value ) {
	return trim( wp_strip_all_tags( (string) $value ) );
}

function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function wp_strip_all_tags( $text ) {
	$text = preg_replace( '/<!--.*?-->/s', ' ', (string) $text );

	return trim( strip_tags( $text ) );
}

function strip_shortcodes( $content ) {
	return preg_replace( '/\[[^\]]+\]/', '', (string) $content );
}

function do_blocks( $content ) {
	return (string) $content;
}

function get_bloginfo( $show = '' ) {
	return 'charset' === $show ? 'UTF-8' : '';
}

function apply_filters( $hook_name, $value ) {
	return $value;
}

function absint( $value ) {
	return abs( (int) $value );
}

function number_format_i18n( $number ) {
	return number_format( (float) $number, 0 );
}

function get_post_types( $args = [], $output = 'names' ) {
	$post_types = [
		'post' => (object) [ 'name' => 'post' ],
		'page' => (object) [ 'name' => 'page' ],
		'book' => (object) [ 'name' => 'book' ],
	];

	return 'objects' === $output ? $post_types : array_keys( $post_types );
}

require_once dirname( __DIR__ ) . '/config/constants.php';
require_once dirname( __DIR__ ) . '/src/Includes/Templates.php';
require_once dirname( __DIR__ ) . '/src/Includes/Reader.php';
require_once dirname( __DIR__ ) . '/src/Includes/Helpers.php';
require_once dirname( __DIR__ ) . '/src/Admin/Upgrades.php';
require_once dirname( __DIR__ ) . '/src/Includes/Shortcodes/Main.php';
