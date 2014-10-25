<?php
/**
 * General
 *
 * This file contains any general functions
 *
 * @package      gsl
 * @author       Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @copyright    Copyright (c) 2012, The College of Wooster
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version      SVN: $Id$
 * @since        1.0
 *
 */

/**
 * Customize columns on gslstaff post type
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $defualts default columns
 * @return      array $defaults modified columns
 * @since       2.0
 *
 */

function gsl_gslstaff_columns($defaults) {
    $columns = array(
        'cb' => '<input type="checkbox" />',
        'title' => __( 'Name' ),
        'gslrole' => __( 'Role' ),
        'date' => __( 'Date' )
    );

    return $columns;
}

/**
 * Add the terms for the role taxonomy to the Role column
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       $column_name
 * @param       $post_id
 * @return      html
 * @since       2.0
 *
 */

function gsl_manage_gslstaff_columns( $column_name, $post_id ) {
    $taxonomy = $column_name;
    $post_type = get_post_type($post_id);
    $terms = get_the_terms($post_id, $taxonomy);

    if ( !empty($terms) ) {
        foreach ( $terms as $term )
            $post_terms[] = "<a href='edit.php?post_type={$post_type}&{$taxonomy}={$term->slug}'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, $taxonomy, 'edit')) . "</a>";
        echo join( ', ', $post_terms );
    }
    else echo '<i>No terms.</i>';
}

/**
 * Designate the Name column as sortable
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $columns
 * @return      array $columns
 * @since       2.0
 *
 */

function gsl_gslstaff_sortable_columns( $columns ) {

    $columns['title'] = 'title';
    return $columns;
}

/**
 * Make the Name column sortable
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $query
 * @return      array $query
 * @since       2.0
 *
 */

function gsl_name_column_orderby( $query ) {
    if( is_admin() ) {
        if (isset($query->query_vars['post_type'])) {
            if ($query->query_vars['post_type'] == 'gslstaff') {

                $query->set('meta_key', 'gsl_last_name_text');
                $query->set('orderby', 'meta_value');
            }
        }
    }
}
add_filter( 'manage_gslstaff_posts_columns', 'gsl_gslstaff_columns' );
add_action( 'manage_gslstaff_posts_custom_column', 'gsl_manage_gslstaff_columns', 10, 2 );
add_filter( 'manage_edit-gslstaff_sortable_columns', 'gsl_gslstaff_sortable_columns' );
add_filter( 'parse_query', 'gsl_name_column_orderby' );

/*
 * Description: Adds a taxonomy filter in the admin list page for a custom post type.
 * Written for: http://yoast.com/custom-post-type-snippets/
 * By: Joost de Valk - http://yoast.com/about-me/
*/

/**
 * Filter the request to just give posts for the given taxonomy, if applicable.
 *
 * @author      yoast
 * @version     SVN: $Id$
 * @since       2.0
 *
 */

function gsl_taxonomy_filter_restrict_manage_posts() {
    global $typenow;

    // If you only want this to work for your specific post type,
    // check for that $type here and then return.
    // This function, if unmodified, will add the dropdown for each
    // post type / taxonomy combination.

    $post_types = get_post_types( array( '_builtin' => false ) );

    if ( in_array( $typenow, $post_types ) ) {
    	$filters = get_object_taxonomies( $typenow );

        foreach ( $filters as $tax_slug ) {
            $tax_obj = get_taxonomy( $tax_slug );
            wp_dropdown_categories( array(
                'show_option_all' => __('Show All '.$tax_obj->label ),
                'taxonomy'      => $tax_slug,
                'name'          => $tax_obj->name,
                'orderby'       => 'name',
                'selected'      => $_GET[$tax_slug],
                'hierarchical'  => $tax_obj->hierarchical,
                'show_count'    => false,
                'hide_empty'    => true
            ) );
        }
    }
}
add_action( 'restrict_manage_posts', 'gsl_taxonomy_filter_restrict_manage_posts' );

/**
 * Add a filter to the query so the dropdown will actually work.
 *
 * @author      yoast
 * @version     SVN: $Id$
 * @since       2.0
 *
 */

function gsl_taxonomy_filter_post_type_request( $query ) {
  global $pagenow, $typenow;

  if ( 'edit.php' == $pagenow ) {
    $filters = get_object_taxonomies( $typenow );
    foreach ( $filters as $tax_slug ) {
      $var = &$query->query_vars[$tax_slug];
      if ( isset( $var ) ) {
        $term = get_term_by( 'id', $var, $tax_slug );
        $var = $term->slug;
      }
    }
  }
}
add_filter( 'parse_query', 'gsl_taxonomy_filter_post_type_request' );

/**
 * Customize posts_per_page on staff archive pages
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $query
 * @return      array $query
 * @since       1.0
 *
 */

function gsl_change_gslstaff_size( $query ) {
    if ( $query->is_main_query() && !is_admin() && is_post_type_archive( 'gslstaff' ) ) { // Make sure it is a archive page
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        $query->set( 'posts_per_page', genesis_get_option( 'gsl_staff_posts_per_page', GSL_SETTINGS_FIELD ) );
        $query->set( 'paged', $paged ); // Set the post archive to be paged
    }
}
add_filter( 'pre_get_posts', 'gsl_change_gslstaff_size' ); // Hook our custom function onto the request filter

/**
 * Customize posts_per_page on role taxonomy pages
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       int $value
 * @return      int $value
 * @since       1.0
 *
 */

function gsl_tax_filter_posts_per_page( $value ) {
    if ( !is_admin() ) {
        return ( is_tax( 'gslrole' ) ) ? genesis_get_option( 'gsl_staff_posts_per_page', GSL_SETTINGS_FIELD ) : $value;
    }
}
add_filter( 'option_posts_per_page', 'gsl_tax_filter_posts_per_page' );

/**
 * Remove support for Title and WYSIWYG editor on gslstaff post type
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_gslstaff_custom_init() {
    remove_post_type_support( 'gslstaff', 'editor' );
    remove_post_type_support( 'gslstaff', 'title' );
}
add_action( 'init', 'gsl_gslstaff_custom_init' );

/**
 * Remove the role taxonomy from the gslstaff post type screen
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @since       1.0
 *
 */

function gsl_remove_custom_taxonomy() {
    remove_meta_box( 'tagsdiv-gslrole', 'gslstaff', 'side' );
}
add_action( 'admin_menu', 'gsl_remove_custom_taxonomy' );

/**
 * Set the title from the first and last name for gslstaff post type
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       string $people_title
 * @return      string $people_title
 * @since       1.0
 *
 */

function gsl_save_new_title( $people_title ) {
      if ($_POST['post_type'] == 'gslstaff') :
           $fname = $_POST['gsl_first_name_text'];
           $lname = $_POST['gsl_last_name_text'];
           $fnamelname  = $fname.' '.$lname;
           $people_title = $fnamelname;
      endif;
      return $people_title;
}
add_filter( 'title_save_pre', 'gsl_save_new_title' );

/**
 * Add filter to ensure the text Staff Member, or staff member, is displayed
 * when user updates a staff member
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $messages
 * @return      array $messages
 * @since       1.0
 *
 */

function gsl_gslstaff_updated_messages( $messages ) {
  global $post, $post_ID;

  $messages['gslstaff'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Staff Memeber updated. <a href="%s">View Staff Member</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Staff Member updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Staff Member restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Staff Memeber published. <a href="%s">View staff member</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Staff Member saved.'),
    8 => sprintf( __('Staff Member submitted. <a target="_blank" href="%s">Preview staff member</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Staff Member scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview staff member</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Staff Member draft updated. <a target="_blank" href="%s">Preview staff member</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}
add_filter( 'post_updated_messages', 'gsl_gslstaff_updated_messages' );

/**
 * Do not display child, grandchild, etc. posts when viewing a parent category
 * and order by title in ascending order unless on the home screen or designated
 * blog page
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $query default query arguments
 * @return      array modified query arguments
 *
 * @since       1.0
 *
 */

function gsl_no_child_posts( $query ) {
    global $wp_query;
    $id = $wp_query->get_queried_object_id();
    if ( !is_home() && !is_category( genesis_get_option( 'gsl_blog_cat', GSL_SETTINGS_FIELD ) ) ) {
        if ( $query->is_category ) {
            $query->set( 'category__in', array( $id ) );
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'asc' );
        }
        return $query;
    }
}
add_action( 'pre_get_posts', 'gsl_no_child_posts' );

/**
 * Make sure to order staff on role term pages alphabetically by last name
 *
 * @author      Jon Breitenbucher <jbreitenbucher@wooster.edu>
 * @version     SVN: $Id$
 * @param       array $query default query arguments
 * @return      array modified query arguments
 *
 * @since       2.0
 *
 */

function gsl_gslstaff_alpha_order_posts( $query ) {

    if ( $query->is_main_query() && !is_admin() && is_tax( 'gslrole' ) && !is_page_template( 'page-gslstaff.php' ) ) {
        $query->set( 'meta_key', 'gsl_last_name_text' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'ASC' );
        return $query;
    }
}
add_action('pre_get_posts', 'gsl_gslstaff_alpha_order_posts');