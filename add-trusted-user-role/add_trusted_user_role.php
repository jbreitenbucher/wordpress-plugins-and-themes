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
 * Add a Trusted Editor role with the exact capabilities of the editor role that will be
 * able to use unfiltered HTML in a multisite where it has been allowed.
 *
 * @uses add_role()
 */

add_role( 'trusted_editor', 'Trusted Editor',
	array( 
		'delete_others_pages' => true,
		'delete_others_posts' => true,
		'delete_pages' => true,
		'delete_posts' => true,
		'delete_private_pages' => true,
		'delete_private_posts' => true,
		'delete_published_pages' => true,
		'delete_published_posts' => true,
		'edit_others_pages' => true,
		'edit_others_posts' => true,
		'edit_pages' => true,
		'edit_posts' => true,
		'edit_private_pages' => true,
		'edit_private_posts' => true,
		'edit_published_pages' => true,
		'edit_published_posts' => true,
		'manage_categories' => true,
		'manage_links' => true,
		'moderate_comments' => true,
		'publish_pages' => true,
		'publish_posts' => true,
		'read' => true,
		'read_private_pages' => true,
		'read_private_posts' => true,
		'unfiltered_html' => true,
		'upload_files' => true
	)
);

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
add_action( 'init', 'remove_editor_unfiltered_html' );

/**
 * Enable unfiltered_html capability for Editors.
 *
 * @param  array  $caps    The user's capabilities.
 * @param  string $cap     Capability name.
 * @param  int    $user_id The user ID.
 * @return array  $caps    The user's capabilities, with 'unfiltered_html' potentially added.
 */
function jb_add_unfiltered_html_capability_to_editors( $caps, $cap, $user_id ) {

	if ( 'unfiltered_html' === $cap && user_can( $user_id, 'trusted_editor' ) ) {

		$caps = array( 'unfiltered_html' );

	}

	return $caps;
}
add_filter( 'map_meta_cap', 'jb_add_unfiltered_html_capability_to_editors', 1, 3 );
?>