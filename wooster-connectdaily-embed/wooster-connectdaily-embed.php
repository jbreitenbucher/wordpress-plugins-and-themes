<?php
/*
Plugin Name: Wooster Connect Daily Embed
Plugin URI: http://technology.spaces.wooster.edu
Description: Adds auto embed support to WordPress for Wooster Connect Daily calendars.
Author: Jon Breitenbucher
Author URI: http://jon.breitenbucher.net
Version: 1.0
*/

/*
Wooster Connect Daily Embed (Wordpress Plugin)
Copyright (C) 2011 Jon Breitenbucher
Contact me at jbreitenbucher@wooster.edu

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
add_action('admin_menu', 'wooster_connectdaily_create_menu');

function wooster_connectdaily_create_menu() {
// create new menu in the Settings panel
	add_options_page('Wooster Connect Daily Embed Options', 'Wooster Connect Daily  Embed', 'manage_options', 'woosterconnectdailyembed_settings_page', 'woosterconnectdailyembed_settings_page');

// call register settings function
	add_action( 'admin_init', 'register_woosterconnectdailyembed_settings' );
}


function register_woosterconnectdailyembed_settings() {
// register our settings
	register_setting( 'woosterconnectdailyembed-settings-group', 'woosterconnectdailyembed_calendar_width' );
	register_setting( 'woosterconnectdailyembed-settings-group', 'woosterconnectdailyembed_calendar_height' );
}

// create the actual contents of the settings page
function woosterconnectdailyembed_settings_page() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
?>
<div class="wrap">
<h2>Wooster Connect Daily Embed Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'woosterconnectdailyembed-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Connect Daily Defaults</th>
        <td>&nbsp;</td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Default width:</th>
        <td><input type="text" name="woosterconnectdailyembed_calendar_width" value="<?php echo get_option('woosterconnectdailyembed_calendar_width'); ?>" /> The default width of the Connect Daily calendar.</td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Default height:</th>
        <td><input type="text" name="woosterconnectdailyembed_calendar_height" value="<?php echo get_option('woosterconnectdailyembed_calendar_height'); ?>" /> The default height of the Connect Daily calendar.</td>
        </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
<?php
wp_embed_register_handler( 'connectdaily', '#https://connectdaily\.wooster\.edu/ViewCal\.html\?dropdown=1&resource_id=([\d]+)#i', 'wp_embed_handler_connectdaily' );

function wp_embed_handler_connectdaily( $matches, $attr, $url, $rawattr ) {
	$width = get_option('woosterconnectdailyembed_calendar_width', '800');
	$height = get_option('woosterconnectdailyembed_calendar_height', '1024');

	$embed = sprintf(
			'<iframe frameborder="0" id="calendarframe" scrolling="auto" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" src="https://connectdaily.wooster.edu/ViewCal.html?dropdown=1&resource_id=%1$s"> Your browser doesn\'t support frames.
Click <a href="https://connectdaily.wooster.edu/ViewCal.html?dropdown=1&resource_id=%1$s">here</a> to view the calendar.
</iframe><br />To reserve this space please visit our <a href="http://calendar.wooster.edu/EditItem.html">Connect Daily Campus Calendar</a>.',
			esc_attr($matches[1])
			);

	return apply_filters( 'embed_connectdaily', $embed, $matches, $attr, $url, $rawattr );
}

?>