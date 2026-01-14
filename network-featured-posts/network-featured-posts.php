<?php
/**
 * Plugin Name:       Network Featured Posts Block
 * Description:       Display posts from across a WordPress Multisite network (indexed + cached).
 * Version:           1.0.0
 * Author:            The College of Wooster
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Network:           true
 * License:           GPL-2.0-or-later
 * Text Domain:       network-featured-posts
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NFP_VERSION', '1.0.0' );
define( 'NFP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NFP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NFP_PLUGIN_DIR . 'includes/activator.php';
require_once NFP_PLUGIN_DIR . 'includes/indexer.php';
require_once NFP_PLUGIN_DIR . 'includes/render.php';
require_once NFP_PLUGIN_DIR . 'includes/rest.php';
require_once NFP_PLUGIN_DIR . 'includes/settings.php';
require_once NFP_PLUGIN_DIR . 'includes/cron.php';

register_activation_hook( __FILE__, 'nfp_activate_plugin' );
register_deactivation_hook( __FILE__, 'nfp_deactivate_plugin' );

/**
 * Register the block.
 */
function nfp_register_block() {
    register_block_type(
        NFP_PLUGIN_DIR . 'build',
        array(
            'render_callback' => 'nfp_render_network_featured_posts_block',
        )
    );
}
add_action( 'init', 'nfp_register_block' );

add_action( 'rest_api_init', 'nfp_register_rest_routes' );
add_action( 'network_admin_menu', 'nfp_register_network_admin_menu' );

// Index updates.
add_action( 'save_post', 'nfp_index_post_on_save', 10, 3 );
add_action( 'before_delete_post', 'nfp_remove_post_from_index', 10, 2 );
add_action( 'transition_post_status', 'nfp_index_post_on_transition', 10, 3 );


/**
 * Ensure the Wooster Blocks category exists in the inserter.
 *
 * @param array        $categories Block categories.
 * @param WP_Post|null $post Post being edited.
 * @return array
 */

/**
 * Ensure the Wooster Blocks category exists in the inserter, and keep it near the top.
 *
 * @param array        $categories Block categories.
 * @param WP_Post|null $post       Post being edited.
 * @return array
 */
function nfp_filter_block_categories_all( $categories, $post ) {
    $target = array(
        'slug'  => 'wbp-content',
        'title' => __( 'Wooster Blocks', 'network-featured-posts' ),
        'icon'  => null,
    );

    $found_index = null;

    foreach ( $categories as $i => $cat ) {
        if ( isset( $cat['slug'] ) && 'wbp-content' === $cat['slug'] ) {
            $found_index = $i;
            break;
        }
    }

    if ( null !== $found_index ) {
        // Remove existing entry so we can reinsert it at the top.
        array_splice( $categories, $found_index, 1 );
    }

    // Put it at the top of the list.
    array_unshift( $categories, $target );

    return $categories;
}
// Priority 0 so we run early and can keep it at/near the top even if others add categories.
add_filter( 'block_categories_all', 'nfp_filter_block_categories_all', 0, 2 );


/**
 * Hide this block from the inserter for non–Super Admins (but keep it registered so it still renders).
 *
 * @param array  $args Block type args.
 * @param string $name Block name.
 * @return array
 */

/**
 * Hide this block from the inserter for non–Super Admins, but keep it registered
 * so it can still render on the front-end.
 *
 * @param array  $args Block type args.
 * @param string $name Block name.
 * @return array
 */
function nfp_filter_register_block_type_args( $args, $name ) {
    if ( 'nfp/network-featured-posts' !== $name ) {
        return $args;
    }

    // Only affect the editor/inserter context.
    if ( is_admin() && is_multisite() && ! is_super_admin() ) {
        if ( ! isset( $args['supports'] ) || ! is_array( $args['supports'] ) ) {
            $args['supports'] = array();
        }
        $args['supports']['inserter'] = false;
    }

    return $args;
}
add_filter( 'register_block_type_args', 'nfp_filter_register_block_type_args', 10, 2 );

