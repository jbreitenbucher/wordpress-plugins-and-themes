<?php
/*
Plugin Name: Add Trusted Roles
Plugin URI: https://technology.wooster.edu
Description: This plugin will add a Trusted Editor and Trusted Administrator role to WordPress. It will allow these roles to use unfiltered HTML in a multisite installation and remove the capability from the Editor and Administrator roles to do so. Otherwise, the Trusted Editor and Trusted Administrator roles have the same capabilities as the Editor and Administrator roles.
Author: Jon Breitenbucher
Author URI: http://jon.breitenbucher.net

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/**
 * Add Trusted Editor and Trusted Administrator roles with the exact capabilities of the Editor and Administrator roles.
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
 * Enable unfiltered_html capability for Trusted Editors and Trusted Administrators in a multisite installation.
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