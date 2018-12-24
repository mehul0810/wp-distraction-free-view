<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
* @since 1.0
*
*/
class WPDFV_Shortcodes {
    
	/*
    * @since 1.0
    *
    */
    public function __construct()
    {
		# Shortcode
        add_shortcode('dfview', array($this, 'dfview_shortcode'));
        
		# Filter
        add_filter( 'the_content_more_link', array($this,'wpdfv_more_link_callback') );
        
		# AJAX
        add_action( 'wp_ajax_display_post_details', array($this,'display_post_details_callback') );
        add_action( 'wp_ajax_nopriv_display_post_details', array($this,'display_post_details_callback') );
    }
    
	/*
    * @since 1.2
    *
    */ 
    public function dfview_shortcode($atts)
    {
        // Contents of this function will execute when the blogger
        // uses the [dfview] shortcode.
        $atts = shortcode_atts( array(
						'post_id' => '',
					), $atts, 'dfview' );
        
        $wpdfv_view_btn_text = get_option('wpdfv_settings_readmore_button_text');
        if(isset($wpdfv_view_btn_text) && '1' == $wpdfv_view_btn_text){
            $wpdfv_view_btn_text = 'DF View';
        }

        $html = '';
        $html .= '<div class="wpdfv-fullscreen-container">';
        $html .= '<button class="btn btn-primary wpdfv-fullscreen-btn" data-post-id="'.$post->ID.'" type="button">'.$wpdfv_view_btn_text.'</button>';
        $html .= '</div>';
        
        return $html;
        
    }
    
    /*
    * @since 1.2
    *
    */
    public function wpdfv_more_link_callback( $link ) {
        global $post;
        $wpdfv_read_more_btn_text = get_option('wpdfv_settings_readmore_button_text',true);
        if(isset($wpdfv_read_more_btn_text) && '1' == $wpdfv_read_more_btn_text){
            $wpdfv_read_more_btn_text = 'Read more';
        }
        
        $wpdfv_button = '';
        $wpdfv_button .= '<div class="wpdfv-fullscreen-container">';
        $wpdfv_button .= '<a class="btn btn-primary" target="_blank" href="'.get_permalink().'">'.$wpdfv_read_more_btn_text.'</a>';
        $wpdfv_button .= '<a href="#" class="wpdfv-fullscreen-btn btn btn-primary" data-post-id="'.$post->ID.'"><i class="fa fa-arrows-alt"></i></a>';
        $wpdfv_button .= '</div>';
        
        return $wpdfv_button;
    }
    
    /*
     * AJAX Call
     * @since 1.0
     *
     */

    public function display_post_details_callback(){
        $post_id = $_POST['id'];

        $post_details = get_post($post_id);

        $html = '';
        $html .= '<h1 class="title">'.$post_details->post_title."</h1>";
		
		# Added Video Support in Pop Up
		$pattern = "#[embed](.*?)[/embed]#";
		$embed_urls = $this->get_all_string_between($post_details->post_content, '[embed]', '[/embed]');
		$post_content = $post_details->post_content;
		if(count($embed_urls) > 0){
			foreach($embed_urls AS $embed_url){
				$converted_embed = wp_oembed_get($embed_url);
				$post_content = str_replace('[embed]'.$embed_url.'[/embed]', $converted_embed, $post_content);
			}
		}
		
        $html .= '<div class="description">'.apply_filters('the_content',$post_content).'</div>';
        echo $html;
        wp_die();
    }
	
	/*
	 *	@since 1.3
	 */
	public function get_all_string_between($string, $start, $end)
	{
		$result = array();
		$string = " ".$string;
		$offset = 0;
		while(true)
		{
			$ini = strpos($string,$start,$offset);
			if ($ini == 0)
				break;
			$ini += strlen($start);
			$len = strpos($string,$end,$ini) - $ini;
			$result[] = substr($string,$ini,$len);
			$offset = $ini+$len;
		}
		return $result;
	}

}

# Class Activation
$wpdfv_shortcodes = new WPDFV_Shortcodes();

/*
 * Filter the_content
 * @since 1.0
 * @depreciated 1.2
 */
//add_filter('the_content','wpdfv_filter_content');
function wpdfv_filter_content($content){
    global $post;
    
    $wpdfv_view_btn_text = get_option('wpt_wpdfv_view_btn_text',true);
    if(isset($wpdfv_view_btn_text) && '1' == $wpdfv_view_btn_text){
        $wpdfv_view_btn_text = 'DF View';
    }
    
    $display_btn_at = get_option('wpt_display_btn_at',true);
    
    $wpdfv_button = '';
    $wpdfv_button .= '<div class="wpdfv-fullscreen-container">';
    $wpdfv_button .= '<button class="btn btn-primary wpdfv-fullscreen-btn" data-post-id="'.$post->ID.'" type="button">'.$wpdfv_view_btn_text.'</button>';
    $wpdfv_button .= '</div>';
    
    if('before_content' == $display_btn_at){
        $wpdfv_content .= $wpdfv_button;
        $wpdfv_content .= $content;
    }elseif('after_content' == $display_btn_at){
        $wpdfv_content .= $content;
        $wpdfv_content .= $wpdfv_button;
    }
    
    return $wpdfv_content;
}
?>