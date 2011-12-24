<?php
error_reporting(E_ALL);
/*
Plugin Name: Require Login to View
Plugin URI: http://orthogonalcreations.com
Description: This plugin will provide a shortcode that can be used to hide text from users who are not logged into the site. If a user is not logged in it will display the login form.
Author: Jon Breitenbucher
Author URI: http://orthogonalcreations.com

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

add_shortcode('requirelogin', 'requirelogin_shortcode');
function requirelogin_shortcode($x,$text=null){
    if(!is_user_logged_in()){ 
				$output = 'You have to been registered and logged in to view the content of this page.<br/>'. wp_login_form()
        return $output;
    }else{ 
        return do_shortcode($text); 
    } 
}
?>