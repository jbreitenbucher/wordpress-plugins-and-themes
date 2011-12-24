<?php
/*
Plugin Name: Add Advisor Role
Plugin URI: http://orthogonalcreations.com
Description: This plugin will add a new Advisor role to WordPress. This role can then be used by other plugins to restrict the viewing of content.
Author: Jon Breitenbucher
Author URI: http://orthogonalcreations.com

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

add_role( 'advisor', 'Advisor', array( 'read_private_pages' => true, 'read_private_posts' => true, 'read' => true ) );
?>