<?php
/**
 * WP Distraction Free View - [wpdfv] shortcode.
 *
 * @since 1.4.2
 *
 * @package    WordPress
 * @subpackage WP Distraction Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

namespace WPDFV\Includes\Shortcodes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Main {

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_shortcode( 'wpdfv', [ $this, 'render_shortcode' ] );

		// Actions.
		add_action( 'wp_ajax_display_post_details', [ $this, 'display_post_details_callback' ] );
		add_action( 'wp_ajax_nopriv_display_post_details', [ $this, 'display_post_details_callback' ] );
	}

	/**
	 * Display Shortcode Content.
	 *
	 * @param array $atts List of shortcode attributes.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'post_id' => 0,
			],
			$atts,
			'wpdfv'
		);

		if ( ! $atts['post_id'] ) {
			global $post;
			$atts['post_id'] = $post->ID;
		}

		$button_text = wpdfv_get_button_text();

		$html  = '';
		$html .= '<div class="wpdfv-fullscreen-container">';
		$html .= sprintf(
			'<a class="wpdfv-fullscreen-btn" data-post-id="%1$s">%2$s</a>',
			$atts['post_id'],
			$button_text
		);
		$html .= '</div>';

		return $html;
	}

	/**
	 * AJAX call to display popup with contents.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function display_post_details_callback() {
		$post_id      = $_POST['id'];
		$post_details = get_post( $post_id );

		ob_start();
		?>
		<div class="wpdfv-popup-wrap">
			<div class="wpdfv-container">
				<h1 class="title">
					<?php echo $post_details->post_title; ?>
				</h1>
				<div class="description">
					<?php echo do_shortcode( $post_details->post_content ); ?>
				</div>
			</div>
		</div>
		<?php
		ob_get_contents();
		wp_die();
	}
}
