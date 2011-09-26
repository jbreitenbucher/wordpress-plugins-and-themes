<?php
/*
Plugin Name: Wooster Video Auto Embed
Plugin URI: http://voices.wooster.edu/
Description: Adds auto embed support to WordPress for Wooster's Adobe Flash server.
Author: Jon Breitenbucher
Author URI: http://jon.breitenbucher.net
Version: 1.0
*/

/*
Wooster Video Auto Embed (Wordpress Plugin)
Copyright (C) 2011 Jon Breitenbucher
Contact me at http://orthogonalcreations.com/contact-me/

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
add_action('admin_menu', 'woostervideoautoembed_create_menu');

function woostervideoautoembed_create_menu() {
// create new menu in the Settings panel
	add_options_page('Wooster Video Auto Embed Options', 'Wooster Video Auto Embed', 'manage_options', 'woostervideoautoembed_settings_page', 'woostervideoautoembed_settings_page');

// call register settings function
	add_action( 'admin_init', 'register_woostervideoautoembed_settings' );
}


function register_woostervideoautoembed_settings() {
// register our settings
	register_setting( 'woostervideoautoembed-settings-group', 'woostervideoautoembed_player_width' );
	register_setting( 'woostervideoautoembed-settings-group', 'woostervideoautoembed_player_height' );
}

// create the actual contents of the settings page
function woostervideoautoembed_settings_page() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
?>
<div class="wrap">
<h2>Wooster Video Auto Embed Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'woostervideoautoembed-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Wooster Video Auto Embed Defaults</th>
        <td>&nbsp;</td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Default width:</th>
        <td><input type="text" name="woostervideoautoembed_player_width" value="<?php echo get_option('woostervideoautoembed_player_width'); ?>" /> The default width of the VoiceThread player.</td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Default height:</th>
        <td><input type="text" name="woostervideoautoembed_player_height" value="<?php echo get_option('woostervideoautoembed_player_height'); ?>" /> The default height of the VoiceThread player.</td>
        </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
<?php
// the magic where we use RegEx to match the URL entered in the post and replace it with the embed code
wp_embed_register_handler( 'woostervideo', '#http://adobeflash.wooster.edu/vod/(.+)#', 'wp_embed_handler_woostervideo');

function right($string,$chars) 
{ 
   $vright = substr($string, strlen($string)-$chars,$chars); 
   return $vright; 
}

function wp_embed_handler_woostervideo( $matches, $attr, $url, $rawattr ) {
	$width = get_option('woostervideoautoembed_player_width', '480');
	$height = get_option('woostervideoautoembed_player_height', '360');
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

	return apply_filters( 'embed_woostervideo', $embed, $matches, $attr, $url, $rawattr );
}

?>