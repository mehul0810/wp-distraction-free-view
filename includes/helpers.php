<?php
/**
 * WPDFV - Helpers
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distration Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This helper function is used to display read mode button.
 *
 * @param int    $id       Post ID.
 *
 * @since 1.4.2
 *
 * @return mixed
 */
function wpdfv_display_read_mode_button( $id = 0 ) {

	// If `$id` is `0`, then get it from `$post` global variable.
	if ( 0 === $id ) {
		global $post;
		$id = $post->ID;
	}

	$btn_text = wpdfv_get_button_text();

	ob_start();
	?>
	<div class="wpdfv-fullscreen-container">
		<button class="btn btn-primary wpdfv-fullscreen-btn" data-post-id="<?php echo $id; ?>" type="button" >
			<?php echo $btn_text; ?>
		</button>
	</div>
	<?php
	ob_get_contents();
}

/**
 * This function is used to get default button text.
 *
 * @since 1.4.2
 *
 * @return string
 */
function wpdfv_get_button_text() {

	$default_text =  __( 'Read Mode', 'wpdfv' );

	return get_option( 'wpt_wpdfv_view_btn_text', $default_text );
}
