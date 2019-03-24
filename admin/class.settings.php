<?php

# Action Initializations
add_action( 'admin_menu', 'wpdfv_add_settings_page_callback' );
add_action( 'admin_init', 'wpdfv_settings_init' );
 
# Functions
function wpdfv_add_settings_page_callback(){
	add_options_page(
		__( 'WP Distraction Free View', 'wpdfv' ),
		__( 'WP Distraction Free View', 'wpdfv' ),
		'manage_options',
		'wpdfv_settings_page',
		'wpdfv_settings_page_callback'
	);
}

function wpdfv_settings_page_callback(){
	
	?>
    <div class="wrap">
    <h1><?php echo __( 'WP Distraction Free View', 'wpdfv' ) . ' Settings';?></h1>
    <form method="POST" action="options.php">
		<?php
        settings_fields( 'wpdfv_general' );
        do_settings_sections( 'wpdfv_general' );
        submit_button();
        ?>
    </form>
    </div>
	<?php
	
}


function wpdfv_settings_init() {

	/* General */
	add_settings_section(
		'wpdfv_settings_section',
		'General Settings',
		'wpdfv_settings_callback',
		'wpdfv_general'
	);
	
	add_settings_field(
		'wpdfv_button_text',
		'Read More Button Text',
		'wpdfv_settings_readmore_btn_text_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	add_settings_field(
		'wpdfv_enable_font_awesome',
		'Enable Font Awesome',
		'wpdfv_settings_enable_font_awesome_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	add_settings_field(
		'wpdfv_enable_print',
		'Print Support',
		'wpdfv_settings_enable_print_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	add_settings_field(
		'wpdfv_enable_fullscreen',
		'Fullscreen Support',
		'wpdfv_settings_enable_fullscreen_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	/* Styling */
	
	add_settings_field(
		'wpdfv_btn_bg_color',
		'Button - Background Color',
		'wpdfv_settings_btn_bg_color_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	add_settings_field(
		'wpdfv_btn_text_color',
		'Button - Text Color',
		'wpdfv_settings_btn_text_color_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);

	add_settings_field(
		'wpdfv_btn_hover_bg_color',
		'Button Hover - Background Color',
		'wpdfv_settings_btn_hover_bg_color_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	add_settings_field(
		'wpdfv_btn_hover_text_color',
		'Button Hover - Text Color',
		'wpdfv_settings_btn_hover_text_color_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);

	add_settings_field(
		'wpdfv_btn_text_fontsize',
		'Button Text Fontsize',
		'wpdfv_settings_btn_text_fontsize_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);

	add_settings_field(
		'wpdfv_btn_icon_fontsize',
		'Button Icon Fontsize',
		'wpdfv_settings_btn_icon_fontsize_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	add_settings_field(
		'wpdfv_btn_padding',
		'Button Padding',
		'wpdfv_settings_btn_padding_callback',
		'wpdfv_general',
		'wpdfv_settings_section'
	);
	
	register_setting( 'wpdfv_general', 'wpdfv_settings_readmore_button_text' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_select_modal' );
    register_setting( 'wpdfv_general', 'wpdfv_settings_enable_font_awesome' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_enable_print' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_enable_fullscreen' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_bg_color' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_text_color' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_hover_bg_color' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_hover_text_color' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_text_fontsize' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_icon_fontsize' );
	register_setting( 'wpdfv_general', 'wpdfv_settings_btn_padding' );
	
} 
 
function wpdfv_settings_callback(){
	# none
}

function wpdfv_settings_readmore_btn_text_callback(){
	echo '<input id="wpdfv_button_text" type="text" name="wpdfv_settings_readmore_button_text" value="'.get_option("wpdfv_settings_readmore_button_text").'" placeholder="Eg. Read More"/>';
}

function wpdfv_settings_select_modal_callback(){
	$html = '';
	$html .= '<select id="wpdfv_select_modal" name="wpdfv_settings_select_modal">';
	if("simple" == get_option("wpdfv_settings_select_modal")){  
		$html .= '<option selected="selected" value="simple">Simple</option>';
	}else{
		$html .= '<option value="simple">Simple</option>';
	}
	if("bootstrap" == get_option("wpdfv_settings_select_modal")){  
		$html .= '<option selected="selected" value="bootstrap">Bootstrap</option>';
	}else{
		$html .= '<option value="bootstrap">Bootstrap</option>';
	}
	$html .= '</select>';
	echo $html;
}

function wpdfv_settings_enable_font_awesome_callback(){
	$checked = '0';	
	if(get_option("wpdfv_settings_enable_font_awesome")){
		$checked = 'checked="checked"';	
	}
	echo '<input id="wpdfv_enable_font_awesome" type="checkbox" '.$checked.' name="wpdfv_settings_enable_font_awesome" value="1" /> Enable';	
}

function wpdfv_settings_enable_print_callback(){
	$checked = '0';	
	if(get_option("wpdfv_settings_enable_print")){
		$checked = 'checked="checked"';	
	}
	echo '<input id="wpdfv_enable_print" type="checkbox" '.$checked.' name="wpdfv_settings_enable_print" value="1" /> Enable';	
}

function wpdfv_settings_enable_fullscreen_callback(){
	$checked = '0';	
	if(get_option("wpdfv_settings_enable_fullscreen")){
		$checked = 'checked="checked"';	
	}
	echo '<input id="wpdfv_enable_fullscreen" type="checkbox" '.$checked.' name="wpdfv_settings_enable_fullscreen" value="1" /> Enable';	
}

function wpdfv_settings_btn_bg_color_callback(){
	echo '<input id="wpdfv_btn_bg_color" type="text" class="color" name="wpdfv_settings_btn_bg_color" value="'.get_option("wpdfv_settings_btn_bg_color").'" />';
}

function wpdfv_settings_btn_text_color_callback(){
	echo '<input id="wpdfv_btn_text_color" type="text" class="color" name="wpdfv_settings_btn_text_color" value="'.get_option("wpdfv_settings_btn_text_color").'" />';
}

function wpdfv_settings_btn_hover_bg_color_callback(){
	echo '<input id="wpdfv_btn_hover_bg_color" type="text" class="color" name="wpdfv_settings_btn_hover_bg_color" value="'.get_option("wpdfv_settings_btn_hover_bg_color").'" />';
}

function wpdfv_settings_btn_hover_text_color_callback(){
	echo '<input id="wpdfv_btn_hover_text_color" type="text" class="color" name="wpdfv_settings_btn_hover_text_color" value="'.get_option("wpdfv_settings_btn_hover_text_color").'" />';
}

function wpdfv_settings_btn_text_fontsize_callback(){
	echo '<input id="wpdfv_btn_text_fontsize" type="number" min="0" name="wpdfv_settings_btn_text_fontsize" value="'.get_option("wpdfv_settings_btn_text_fontsize").'" /> PX';
}

function wpdfv_settings_btn_icon_fontsize_callback(){
	echo '<input id="wpdfv_btn_icon_fontsize" type="number" min="0" name="wpdfv_settings_btn_icon_fontsize" value="'.get_option("wpdfv_settings_btn_icon_fontsize").'" /> PX';
}

function wpdfv_settings_btn_padding_callback(){
	echo '<input id="wpdfv_btn_padding" type="text" name="wpdfv_settings_btn_padding" value="'.get_option("wpdfv_settings_btn_padding").'" /> <em>Eg. 5px 10px 15px 20px</em>';	
}