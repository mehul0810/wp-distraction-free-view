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
 * @param string $btn_text Button Text.
 *
 * @since 1.4.2
 *
 * @return mixed
 */
function wpdfv_display_read_mode_button( $id = 0, $btn_text = '' ) {

	// If `$id` is `0`, then get it from `$post` global variable.
	if ( 0 === $id ) {
		global $post;
		$id = $post->ID;
	}

	// If read mode button text doesn't exist then display default button text.
	if ( empty( $btn_text ) ) {
		$btn_text = __( 'Read Mode', 'wpdfv' );
	}

	ob_start();
	?>
	<div class="wpdfv-fullscreen-container">
		<button class="btn btn-primary wpdfv-fullscreen-btn" data-post-id="<?php echo $id; ?>" type="button" >
			<?php echo $btn_text; ?>
		</button>
	</div>
	<?php
	return ob_get_contents();
}
