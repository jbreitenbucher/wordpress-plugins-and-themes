<?php
/**
 * Plugin Name:       Add Trusted Roles
 * Description:       This plugin will add a Trusted Editor and Trusted Administrator role to WordPress
 *                    Multisite. It will allow these roles to use unfiltered HTML. Otherwise, the Trusted 
 *                    Editor and Trusted Administrator roles have the same capabilities as the Editor and
 *                    Administrator roles.
 * Version:           1.1.3
 * Author:            The College of Wooster
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Network:           true
 * License:           GPL-2.0-or-later
 * Text Domain:       add-trusted-roles
 * Domain Path:       /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'COW_ATR_OPTION_PREV_CAPS', 'cow_atr_prev_unfiltered_html_caps' );
define( 'COW_ATR_OPTION_PREV_CAPS_LEGACY', 'woo_atr_prev_unfiltered_html_caps' );

/**
 * Get previous unfiltered_html cap state from options, with backward-compatible fallback.
 *
 * @return array|null
 */
function cow_atr_get_prev_caps_option() {
	$prev = get_option( COW_ATR_OPTION_PREV_CAPS, null );
if ( null === $prev && defined( 'COW_ATR_OPTION_PREV_CAPS_LEGACY' ) ) {
		$prev = get_option( COW_ATR_OPTION_PREV_CAPS_LEGACY, null );
		// Migrate forward if legacy exists.
		if ( null !== $prev ) {
			update_option( COW_ATR_OPTION_PREV_CAPS, $prev, false );
			delete_option( COW_ATR_OPTION_PREV_CAPS_LEGACY );
		}
	}
	return $prev;
}

/**
 * Apply the plugin's role/cap changes for the current site.
 *
 * - Adds Trusted Editor/Administrator roles (cloned from the base roles).
 * - Removes unfiltered_html from Editor/Administrator, preserving previous state.
 */
function cow_atr_apply_for_site() {
	$editor        = get_role( 'editor' );
	$administrator = get_role( 'administrator' );

	// Add trusted roles if base roles exist.
	if ( $editor && ! get_role( 'trusted_editor' ) ) {
		add_role( 'trusted_editor', __( 'Trusted Editor', 'add-trusted-roles' ), $editor->capabilities );
	}

	if ( $administrator && ! get_role( 'trusted_administrator' ) ) {
		add_role( 'trusted_administrator', __( 'Trusted Administrator', 'add-trusted-roles' ), $administrator->capabilities );
	}

	if ( ! is_multisite() ) {
		// Snapshot whether the base roles previously had unfiltered_html, so we can restore on deactivation.
		$prev = get_option( COW_ATR_OPTION_PREV_CAPS, null );
if ( null === $prev ) {
			$prev = array(
				'editor'        => ( $editor && $editor->has_cap( 'unfiltered_html' ) ),
				'administrator' => ( $administrator && $administrator->has_cap( 'unfiltered_html' ) ),
			);
			add_option( COW_ATR_OPTION_PREV_CAPS, $prev, '', false );
		}

		// Enforce: editors and admins should not have unfiltered_html.
		global $wp_roles;
		if ( $wp_roles instanceof WP_Roles ) {
			$wp_roles->remove_cap( 'editor', 'unfiltered_html' );
			$wp_roles->remove_cap( 'administrator', 'unfiltered_html' );
		}
	}

}


/**
 * Remove the plugin's role/cap changes for the current site.
 *
 * - Removes Trusted Editor/Administrator roles.
 * - Restores unfiltered_html for Editor/Administrator to their prior state.
 */
function cow_atr_remove_for_site() {
	// Remove trusted roles.
	remove_role( 'trusted_editor' );
	remove_role( 'trusted_administrator' );

	$prev = get_option( COW_ATR_OPTION_PREV_CAPS, null );
if ( is_array( $prev ) ) {
		$editor        = get_role( 'editor' );
		$administrator = get_role( 'administrator' );
		if ( ! is_multisite() ) {
			if ( $editor ) {
				if ( ! empty( $prev['editor'] ) ) {
					$editor->add_cap( 'unfiltered_html' );
				} else {
					$editor->remove_cap( 'unfiltered_html' );
				}
			}

			if ( $administrator ) {
				if ( ! empty( $prev['administrator'] ) ) {
					$administrator->add_cap( 'unfiltered_html' );
				} else {
					$administrator->remove_cap( 'unfiltered_html' );
				}
			}
		}


		delete_option( COW_ATR_OPTION_PREV_CAPS );
	}
}


/**
 * Run on activation.
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 */
function cow_atr_activate( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		$sites = get_sites( array( 'fields' => 'ids' ) );
		foreach ( $sites as $site_id ) {
			switch_to_blog( (int) $site_id );
			cow_atr_apply_for_site();
			restore_current_blog();
		}
		return;
	}

	cow_atr_apply_for_site();
}
register_activation_hook( __FILE__, 'cow_atr_activate' );


/**
 * Run on deactivation.
 *
 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
 */
function cow_atr_deactivate( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		$sites = get_sites( array( 'fields' => 'ids' ) );
		foreach ( $sites as $site_id ) {
			switch_to_blog( (int) $site_id );
			cow_atr_remove_for_site();
			restore_current_blog();
		}
		return;
	}

	cow_atr_remove_for_site();
}
register_deactivation_hook( __FILE__, 'cow_atr_deactivate' );


/**
 * Ensure new sites get the roles/cap rules when the plugin is network-activated.
 *
 * @param WP_Site $new_site New site object.
 */
function cow_atr_on_initialize_site( $new_site ) {
	if ( ! ( $new_site instanceof WP_Site ) || ! is_multisite() ) {
		return;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		return;
	}

	switch_to_blog( (int) $new_site->blog_id );
	cow_atr_apply_for_site();
	restore_current_blog();
}
add_action( 'wp_initialize_site', 'cow_atr_on_initialize_site', 10, 1 );


/**
 * Back-compat for older multisite site-creation hook.
 *
 * @param int $blog_id New blog ID.
 */
function cow_atr_on_wpmu_new_blog( $blog_id ) {
	if ( ! is_multisite() ) {
		return;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		return;
	}

	switch_to_blog( (int) $blog_id );
	cow_atr_apply_for_site();
	restore_current_blog();
}
add_action( 'wpmu_new_blog', 'cow_atr_on_wpmu_new_blog', 10, 1 );

/**
 * Add Trusted Editor and Trusted Administrator roles with the exact capabilities of
 * the Editor and Administrator roles.
 *
 * @uses add_role()
 */

// Keep enforcing the cap removal at runtime, in case another plugin/theme re-adds it.
add_action( 'init', 'cow_atr_apply_for_site' );


/**
 * Enable unfiltered_html capability for Trusted Editors and Trusted Administrators in
 * a multisite installation.
 *
 * @param  array  $caps    The user's capabilities.
 * @param  string $cap     Capability name.
 * @param  int    $user_id The user ID.
 * @return array  $caps    The user's capabilities, with 'unfiltered_html' potentially added.
 */
function cow_atr_map_meta_cap_unfiltered_html( $caps, $cap, $user_id ) {
	if ( 'unfiltered_html' === $cap && ( user_can( $user_id, 'trusted_editor' ) || user_can( $user_id, 'trusted_administrator' ) ) ) {
		$caps = array( 'unfiltered_html' );
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'cow_atr_map_meta_cap_unfiltered_html', 1, 3 );
