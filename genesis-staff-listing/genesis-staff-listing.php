<?php
error_reporting(E_ALL);
/*
Plugin Name: Genesis Staff Listing
Plugin URI: http://orthogonalcreations.com
Description: This plugin adds a Staff custom post type and role taxonomy. It also includes templates for displaying the Staff post type.
Author: Jon Breitenbucher
Author URI: http://orthogonalcreations.com

Version: 1.1

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/** Define our settings */
define( 'GSL_SETTINGS_FIELD', 'gsl-settings' );

/** Admin */
require_once( plugin_dir_path( __FILE__ ) . 'lib/admin/admin.php' );

/** Add support for theme options */
add_action( 'admin_init', 'gsl_reset' );
add_action( 'admin_init', 'gsl_register_settings' );
add_action( 'admin_menu', 'gsl_add_menu', 100);
add_action( 'admin_notices', 'gsl_notices' );
add_action( 'genesis_settings_sanitizer_init', 'gsl_staff_sanitization_filters' );

/** Add new featured image sizes */
add_image_size('profile-picture-listing', 325, 183, TRUE);
add_image_size('profile-picture-single', 325, 183, TRUE);

// Functions
require_once( plugin_dir_path( __FILE__ ) . 'lib/functions/general.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/functions/post-types.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/functions/taxonomies.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/functions/metaboxes.php' );

// CSS
/**
* Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript
*/
add_action( 'wp_enqueue_scripts', 'gsl_add_stylesheet' );

/**
* Enqueue plugin style-file
*/
function gsl_add_stylesheet() {
// Respects SSL, Style.css is relative to the current file
    wp_register_style( 'gsl-style', plugins_url('css/gsl-style.css', __FILE__) );
    wp_enqueue_style( 'gsl-style' );
}

function gsl_locate_plugin_template($template_names, $load = false, $require_once = true )
{
    if ( !is_array($template_names) )
        return '';

    $located = '';

    $this_plugin_dir = WP_PLUGIN_DIR.'/'.str_replace( basename( __FILE__), "", plugin_basename(__FILE__) );

    foreach ( $template_names as $template_name ) {
        if ( !$template_name )
            continue;
        if ( file_exists(STYLESHEETPATH . '/' . $template_name)) {
            $located = STYLESHEETPATH . '/' . $template_name;
            break;
        } else if ( file_exists(TEMPLATEPATH . '/' . $template_name) ) {
            $located = TEMPLATEPATH . '/' . $template_name;
            break;
        } else if ( file_exists( $this_plugin_dir . 'templates/' . $template_name) ) {
            $located =  $this_plugin_dir . 'templates/' . $template_name;
            break;
        }
    }

    if ( $load && '' != $located )
        load_template( $located, $require_once );

    return $located;
}
add_filter( 'taxonomy_template', 'gsl_get_custom_taxonomy_template' );
add_filter( 'single_template', 'gsl_get_custom_single_template' );
add_filter( 'template_redirect', 'gsl_page_redirect');
function gsl_get_custom_taxonomy_template($template)
{
    $taxonomy = get_query_var('taxonomy');

    if ( 'gslrole' == $taxonomy ||  'gslexpertise' == $taxonomy ) {
        $term = get_query_var('term');

        $templates = array();
        if ( $taxonomy && $term )
                $templates[] = "taxonomy-$taxonomy-$term.php";
        if ( $taxonomy )
                $templates[] = "taxonomy-$taxonomy.php";

        $templates[] = "taxonomy.php";
        $template = gsl_locate_plugin_template($templates);
    }
     return $template;
}
function gsl_get_custom_single_template($template)
{
    global $wp_query;
    $object = $wp_query->get_queried_object();

    if ( 'gslstaff' == $object->post_type ) {
        $templates = array('single-' . $object->post_type . '.php', 'single.php');
        $template = gsl_locate_plugin_template($templates);
    }
     return $template;
}
function gsl_page_redirect() {
    $pagename = get_query_var('pagename');
    $plugindir = dirname( __FILE__ );
        if ($pagename == genesis_get_option( 'gsl_staff_page', GSL_SETTINGS_FIELD ) ) {
            $templatefilename = 'page-gslstaff.php';
                if (file_exists(TEMPLATEPATH . '/' . $templatefilename)) {
                    $return_template = TEMPLATEPATH . '/' . $templatefilename;
                } else {
                    $return_template = $plugindir . '/templates/' . $templatefilename;
                }
                    do_theme_redirect($return_template);
        }
    }
function do_theme_redirect($url) {
    global $post, $wp_query;
        if (have_posts()) {
            include($url);
            die();
        } else {
            $wp_query->is_404 = true;
        }
    }
?>