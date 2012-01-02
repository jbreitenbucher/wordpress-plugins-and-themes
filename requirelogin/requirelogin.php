<?php
/*
Plugin Name: Require Login to View
Plugin URI: http://thepedestalgroup.com
Description: This plugin will provide a shortcode that can be used to hide text from users who are not logged into the site. If a user is not logged in it will display the login form.
Author: The Pedestal Group
Author URI: http://thepedestalgroup.com

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// create custom plugin settings menu
add_action('admin_menu', 'requirelogin_create_menu');

function requirelogin_create_menu() {
// create new menu in the Settings panel
	add_options_page('Require Login Options', 'Require Login', 'manage_options', 'requirelogin_settings_page', 'requirelogin_settings_page');

// call register settings function
	add_action( 'admin_init', 'requirelogin_register_mysettings' );
}


function requirelogin_register_mysettings() {
// register our settings
	register_setting( 'requirelogin-settings-group', 'requirelogin_message', 'wp_kses_post' );
}

// create the actual contents of the settings page
function requirelogin_settings_page() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
?>
<div class="wrap">
<h2>Require Login Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'requirelogin-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Message</th>
        <td><input type="text" name="requirelogin_message" size="100" value="<?php echo esc_html(get_option('requirelogin_message','You must be registered and logged in to view the content of this page.')); ?>" /></td>
      </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
<?php
add_filter('the_posts', 'conditionally_add_scripts_and_styles'); // the_posts gets triggered before wp_head
function conditionally_add_scripts_and_styles($posts){
	if (empty($posts)) return $posts;
 
	$shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
	foreach ($posts as $post) {
		if (stripos($post->post_content, '[requirelogin]') !== false) {
			$shortcode_found = true; // bingo!
			break;
		}
	}
 
	if ($shortcode_found) {
		// enqueue here
		wp_enqueue_style('my-style', plugins_url('style.css', __FILE__));
	}
 
	return $posts;
}

add_shortcode('requirelogin', 'requirelogin_shortcode');
function requirelogin_shortcode($x,$text=null){
    if(!is_user_logged_in()){ 
			return '<div class="login"><p>'. get_option('requirelogin_message') . '</p>' . wp_login_form(array('echo' => false)) . '</div>';
    }else{ 
        return do_shortcode($text); 
    } 
}
?>