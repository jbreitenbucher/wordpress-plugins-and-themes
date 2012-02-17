<?php
/*
Plugin Name: WooTube Auto Embed
Plugin URI: http://instructionaltechnology.wooster.edu
Description: Adds auto embed support to WordPress for Wooster's Adobe Flash server service called WooTube.
Author: Jon Breitenbucher
Author URI: http://jon.breitenbucher.net
Version: 1.0
*/

/*
WooTube Auto Embed (Wordpress Plugin)
Copyright (C) 2011 The College of Wooster
Contact me at http://instructionaltechnology.wooster.edu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// create custom plugin settings menu
add_action('admin_menu', 'wootubeautoembed_create_menu');

function wootubeautoembed_create_menu() {
// create new menu in the Settings panel
	add_options_page('WooTube Auto Embed Options', 'WooTube Auto Embed', 'manage_options', 'wootubeautoembed_settings_page', 'wootubeautoembed_settings_page');

// call register settings function
	add_action( 'admin_init', 'register_wootubeautoembed_settings' );
}


function register_wootubeautoembed_settings() {
// register our settings
	register_setting( 'wootubeautoembed-settings-group', 'wootubeautoembed-settings-group', 'wootubeautoembed_validate' );
	add_settings_section('wootubeautoembed_dimensions', 'Video dimensions', 'wootubeautoembed_section_text','wootubeautoembed');
		add_settings_field('wootubeautoembed_player_width','Width of embedded video','wootubeautoembed_width','wootubeautoembed','wootubeautoembed_dimensions');
	add_settings_field('wootubeautoembed_player_height','Height of embedded video','wootubeautoembed_height','wootubeautoembed','wootubeautoembed_dimensions');
}

function wootubeautoembed_section_text() {
	echo '<p>Use these options to set the width and height used to display embedded WooTube videos in your posts.</p>';
}

function wootubeautoembed_width() {
	$options = get_option('wootubeautoembed-settings-group');
	echo "<input id='wootubeautoembed_player_width' type='text' name='wootubeautoembed-settings-group[wootubeautoembed_player_width]' size='10' value='{$options['wootubeautoembed_player_width']}' /> The default width of the WooTube video.";
}

function wootubeautoembed_height() {
	$options = get_option('wootubeautoembed-settings-group');
	echo "<input id='wootubeautoembed_player_height' type='text' name='wootubeautoembed-settings-group[wootubeautoembed_player_height]' size='10' value='{$options['wootubeautoembed_player_height']}' /> The default height of the WooTube video.";
}

function wootubeautoembed_validate($input){
	$options = get_option('wootubeautoembed-settings-group');
	$options['wootubeautoembed_player_width'] = trim($input['wootubeautoembed_player_width']);
	$options['wootubeautoembed_player_height'] = trim($input['wootubeautoembed_player_height']);
	if(!(int) $options['wootubeautoembed_player_width'] || !(int) $options['wootubeautoembed_player_height'] ) {
		$options['wootubeautoembed_player_width'] = '';
		$options['wootubeautoembed_player_height'] = '';
	}
	return $options;
}

// create the actual contents of the settings page
function wootubeautoembed_settings_page() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
?>
<div class="wrap">
<h2>WooTube Auto Embed Options</h2>

<form method="post" action="options.php">
	<?php settings_fields( 'wootubeautoembed-settings-group' ); ?>
	<?php do_settings_sections( 'wootubeautoembed' ); ?>
    
    <p class="submit">
    <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
<?php
// the magic where we use RegEx to match the URL entered in the post and replace it with the embed code

wp_embed_register_handler( 'wootubevideo', '#http://adobeflash.wooster.edu/vod/(.+)#', 'wp_embed_handler_wootubevideo');

function right($string,$chars) 
{ 
   $vright = substr($string, strlen($string)-$chars,$chars); 
   return $vright; 
}

function wp_embed_handler_wootubevideo( $matches, $attr, $url, $rawattr ) {
	$width = get_option('wootubeautoembed_player_width', '480');
	$height = get_option('wootubeautoembed_player_height', '360');
	$path = '';

	if(strcmp(right(strtolower($matches[1]),3), 'flv')==0)
	{
	 $path = str_replace('.flv', '', strtolower($matches[1]));
	}
	else
	{
	 $path = 'mp4:' . $matches[1];
	}

	echo $path;

	$embed = sprintf(
			'<object id="flowplayer" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" data="http://webapps.wooster.edu/player/swf/flowplayer.swf" type="application/x-shockwave-flash"><param name="movie" value="http://webapps.wooster.edu/player/swf/flowplayer.swf" /><param name="allowfullscreen" value="true" /><param name="flashvars" value=\'config={"key":"#$28d4f4f3f205e518e4a","clip":{"provider":"rtmp","live":true,"url":  "' . $path . '","autoBuffering":true,"autoPlay": true  },"plugins":{"rtmp":{"url":"flowplayer.rtmp.swf","netConnectionUrl":"rtmp://adobeflash.wooster.edu/vod"}}}\' /></object>',
			esc_attr($matches[1])
			);

	return apply_filters( 'embed_wootubevideo', $embed, $matches, $attr, $url, $rawattr );
}

?>