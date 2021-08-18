<?php
/*
Plugin Name: Add Trusted Editor Role
Plugin URI: https://technology.wooster.edu
Description: This plugin will add a new Trusted User role to WordPress. This role will have all the capabilities of the Editot role but with the additional ability to use unfiltered_html in a Miltisite installation. This role can also be used by other plugins to restrict the viewing of content.
Author: Jon Breitenbucher
Author URI: http://jon.breitenbucher.net

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/**
 * Add a Trusted Editor and Trusted Administrator roles with the exact capabilities of the editor and administrator roles that will be
 * able to use unfiltered HTML in a multisite where it has been allowed.
 *
 * @uses add_role()
 */

function wooster_roles(){

	add_role( 'trusted_editor', 'Trusted Editor', get_role( 'editor' )->capabilities );
	add_role( 'trusted_administrator', 'Trusted Administrator', get_role( 'administrator' )->capabilities );
}
add_action( 'init', 'wooster_roles' );

/**
 * Don't let editors or administrators use unfiltered HTML in a multisite where it has been allowed.
 *
 *
 * You should call the function when your plugin is activated.
 *
 * @uses $wp_roles
 * @uses WP_Roles::remove_cap()
 */
function wooster_remove_unfiltered_html(){
 
    // $wp_roles is an instance of WP_Roles.
    global $wp_roles;
    $wp_roles->remove_cap( 'editor', 'unfiltered_html' );
    $wp_roles->remove_cap( 'administrator', 'unfiltered_html' );
}
add_action( 'init', 'wooster_remove_unfiltered_html' );

/**
 * Don't let editors use unfiltered HTML in a multisite where it has been allowed.
 *
 * @uses WP_Role::remove_cap()
 */
function remove_editor_unfiltered_html() {
 
    // get_role returns an instance of WP_Role.
    $role = get_role( 'editor' );
    $role->remove_cap( 'unfiltered_html' );
}
//add_action( 'init', 'remove_editor_unfiltered_html' );

/**
 * Enable unfiltered_html capability for Editors.
 *
 * @param  array  $caps    The user's capabilities.
 * @param  string $cap     Capability name.
 * @param  int    $user_id The user ID.
 * @return array  $caps    The user's capabilities, with 'unfiltered_html' potentially added.
 */
function wooster_add_unfiltered_html_capability( $caps, $cap, $user_id ) {

	if ( 'unfiltered_html' === $cap && (user_can( $user_id, 'trusted_editor' ) || user_can( $user_id, 'trusted_administrator' )) ) {

		$caps = array( 'unfiltered_html' );

	}

	return $caps;
}
add_filter( 'map_meta_cap', 'wooster_add_unfiltered_html_capability', 1, 3 );
?>