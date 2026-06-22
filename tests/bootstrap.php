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

$GLOBALS['wpdfv_test_active_plugins'] = [];
$GLOBALS['wpdfv_test_enqueued']       = [
	'scripts' => [],
	'styles'  => [],
	'inline'  => [],
];
$GLOBALS['wpdfv_test_options']        = [];
$GLOBALS['wpdfv_test_filters']        = [];
$GLOBALS['wpdfv_test_plugins']        = [];
$GLOBALS['wpdfv_test_posts']          = [];
$GLOBALS['wpdfv_test_shortcodes']     = [];

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

		/**
		 * Post status.
		 *
		 * @var string
		 */
		public $post_status = 'publish';
	}
}

require_once __DIR__ . '/shims/WP_Error.php';
require_once __DIR__ . '/shims/WP_REST_Request.php';
require_once __DIR__ . '/shims/WP_REST_Response.php';

function wpdfv_tests_reset_state() {
	$GLOBALS['wpdfv_test_active_plugins'] = [];
	$GLOBALS['wpdfv_test_enqueued']       = [
		'scripts' => [],
		'styles'  => [],
		'inline'  => [],
	];
	$GLOBALS['wpdfv_test_options']        = [];
	$GLOBALS['wpdfv_test_filters']        = [];
	$GLOBALS['wpdfv_test_plugins']        = [];
	$GLOBALS['wpdfv_test_posts']          = [];
	$GLOBALS['wpdfv_test_shortcodes']     = [];
	$_GET                                 = [];

	if ( class_exists( '\WPDFV\Includes\Actions' ) ) {
		$frontend_settings_added = new ReflectionProperty( '\WPDFV\Includes\Actions', 'frontend_settings_added' );
		$frontend_settings_added->setAccessible( true );
		$frontend_settings_added->setValue( null, false );
	}
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

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wpdfv_test_filters'][ $hook_name ][ $priority ][] = [
		'callback'      => $callback,
		'accepted_args' => $accepted_args,
	];

	return true;
}

function add_shortcode( $tag, $callback ) {
	$GLOBALS['wpdfv_test_shortcodes'][ $tag ] = $callback;

	return true;
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$atts = (array) $atts;
	$out  = [];

	foreach ( $pairs as $name => $default ) {
		$out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
	}

	return $out;
}

function current_user_can( $capability, ...$args ) {
	return true;
}

function is_multisite() {
	return false;
}

function wp_register_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
	return true;
}

function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
	$GLOBALS['wpdfv_test_enqueued']['styles'][] = $handle;

	return true;
}

function wp_register_script( $handle, $src = '', $deps = [], $ver = false, $args = [] ) {
	return true;
}

function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $args = [] ) {
	$GLOBALS['wpdfv_test_enqueued']['scripts'][] = $handle;

	return true;
}

function wp_set_script_translations( $handle, $domain = 'default', $path = '' ) {
	return true;
}

function wp_add_inline_script( $handle, $data, $position = 'after' ) {
	$GLOBALS['wpdfv_test_enqueued']['inline'][ $handle ][] = $data;

	return true;
}

function wp_json_encode( $value, $flags = 0, $depth = 512 ) {
	return json_encode( $value, $flags, $depth );
}

function get_plugins() {
	return $GLOBALS['wpdfv_test_plugins'];
}

function is_plugin_active( $plugin ) {
	return in_array( $plugin, $GLOBALS['wpdfv_test_active_plugins'], true );
}

function activate_plugin( $plugin, $redirect = '', $network_wide = false, $silent = false ) {
	$GLOBALS['wpdfv_test_active_plugins'][] = $plugin;
	$GLOBALS['wpdfv_test_active_plugins']   = array_values( array_unique( $GLOBALS['wpdfv_test_active_plugins'] ) );

	return null;
}

function is_wp_error( $thing ) {
	return class_exists( 'WP_Error' ) && $thing instanceof \WP_Error;
}

function get_post( $post_id = null ) {
	$post_id = absint( $post_id );

	return $GLOBALS['wpdfv_test_posts'][ $post_id ] ?? null;
}

function get_post_type( $post = null ) {
	if ( $post instanceof WP_Post ) {
		return $post->post_type;
	}

	$post = get_post( $post );

	return $post instanceof WP_Post ? $post->post_type : false;
}

// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- WordPress core function shim.
function get_the_ID() {
	global $post;

	return $post instanceof WP_Post ? $post->ID : 0;
}

function get_the_title( $post = 0 ) {
	$post = $post instanceof WP_Post ? $post : get_post( $post );

	return $post instanceof WP_Post ? 'Test title' : '';
}

function get_block_wrapper_attributes( $attributes = [] ) {
	$class_name = isset( $attributes['class'] ) ? $attributes['class'] : '';

	return '' !== $class_name ? 'class="' . esc_attr( $class_name ) . '"' : '';
}

function post_password_required( $post = null ) {
	return false;
}

function get_post_status( $post = null ) {
	$post = $post instanceof WP_Post ? $post : get_post( $post );

	return $post instanceof WP_Post ? $post->post_status : false;
}

function is_post_publicly_viewable( $post = null ) {
	$post = $post instanceof WP_Post ? $post : get_post( $post );

	return $post instanceof WP_Post && 'publish' === $post->post_status;
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

function esc_url_raw( $url ) {
	return filter_var( (string) $url, FILTER_SANITIZE_URL );
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

function wp_kses_post( $content ) {
	return preg_replace( '#</?(script|style|noscript)\b[^>]*>#i', '', (string) $content );
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

function apply_filters( $hook_name, $value, ...$args ) {
	if ( empty( $GLOBALS['wpdfv_test_filters'][ $hook_name ] ) ) {
		return $value;
	}

	ksort( $GLOBALS['wpdfv_test_filters'][ $hook_name ] );

	foreach ( $GLOBALS['wpdfv_test_filters'][ $hook_name ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$accepted_args = max( 1, (int) $callback['accepted_args'] );
			$value         = call_user_func_array( $callback['callback'], array_slice( array_merge( [ $value ], $args ), 0, $accepted_args ) );
		}
	}

	return $value;
}

function rest_ensure_response( $response ) {
	return $response instanceof WP_REST_Response ? $response : new WP_REST_Response( $response );
}

function setup_postdata( $post ) {
	$GLOBALS['post'] = $post;

	return true;
}

function wp_reset_postdata() {
	unset( $GLOBALS['post'] );
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
require_once dirname( __DIR__ ) . '/src/Includes/Actions.php';
require_once dirname( __DIR__ ) . '/src/Includes/Blocks.php';
require_once dirname( __DIR__ ) . '/src/Admin/Upgrades.php';
require_once dirname( __DIR__ ) . '/src/Admin/SettingsApi.php';
require_once dirname( __DIR__ ) . '/src/Includes/Shortcodes/Main.php';
require_once dirname( __DIR__ ) . '/src/Plugin.php';
require_once __DIR__ . '/TestableSettingsApi.php';
