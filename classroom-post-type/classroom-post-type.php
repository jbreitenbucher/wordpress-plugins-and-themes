<?php
error_reporting(E_ALL);
/*
Plugin Name: Classroom Post Type
Plugin URI: http://orthogonalcreations.com
Description: Adds a custom post type and several custom taxonomies for entering classroom information.
Author: Jon Breitenbucher
Author URI: http://orthogonalcreations.com

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/** Register a custom post type for Staff */
add_action( 'init', 'create_classroom_post_type' );

function create_classroom_post_type() {
	$labels = array(
		'name' => _x('Classrooms', 'post type general name'),
		    'singular_name' => _x('Classroom', 'post type singular name'),
		    'add_new' => _x('Add New', 'person'),
		    'add_new_item' => __('Add New Classroom'),
		    'edit_item' => __('Edit Classroom'),
		    'new_item' => __('New Classroom'),
		    'all_items' => __('All Classrooms'),
		    'view_item' => __('View Classroom'),
		    'search_items' => __('Search Classrooms'),
		    'not_found' =>  __('No classrooms found'),
		    'not_found_in_trash' => __('No classrooms found in Trash'), 
		    'parent_item_colon' => '',
		    'menu_name' => 'Classrooms'
	);
	$args = array(
		'labels' => $labels,
		'description' => 'A post type for entering classroom information.',
		'public' => true,
		'hierarchical' => false,
		'supports' => array('thumbnail','excerpt'),
		'rewrite' => array('slug' => 'classrooms'),
		'has_archive' => true,
	);
	register_post_type('classroom',$args);
}

/** Customize the icon for the classroom post type */
add_action('admin_head', 'set_classroom_icon');
function set_classroom_icon() {
	global $post_type;
	?>
	<style>
	<?php if (($_GET['post_type'] == 'classroom') || ($post_type == 'classroom')) : ?>
	#icon-edit { background:transparent url('<?php echo get_bloginfo('url');?>/wp-admin/images/icons32.png') no-repeat -600px -5px; }
	<?php endif; ?>
 
	#adminmenu #menu-posts-itpeople div.wp-menu-image{background:transparent url('<?php echo get_bloginfo('url');?>/wp-admin/images/menu.png') no-repeat scroll -300px -33px;}
	#adminmenu #menu-posts-classroom:hover div.wp-menu-image,#adminmenu #menu-posts-classroom.wp-has-current-submenu div.wp-menu-image{background:transparent url('<?php echo get_bloginfo('url');?>/wp-admin/images/menu.png') no-repeat scroll -300px -1px;}		
        </style>
        <?php
}

/** Remove support for WYSIWYG editor on classroom post type */
add_action('init', 'classroom_custom_init');
	function classroom_custom_init() {
		remove_post_type_support('classroom', 'editor');
	}

/**
 * Create a custom Metabox for the classroom post type
 *
 * @link http://www.billerickson.net/wordpress-metaboxes/
 *
 */
function classroom_create_metaboxes( $meta_boxes ) {
	$prefix = 'it_'; // start with an underscore to hide fields from custom fields list
	$meta_boxes[] = array(
	'id' => 'info_metabox',
	'title' => 'Information',
	'pages' => array('classroom'), // post type
	'context' => 'normal',
	'priority' => 'low',
	'show_names' => true, // Show field names on the left
	'fields' => array(
		array(
			'name' => 'Notes',
			'desc' => 'Give a brief description of the classroom.',
			'id' => $prefix . 'notes_wysiwyg',
			'type' => 'wysiwyg'
		),
		array(
			'name' => 'Building',
			'desc' => '',
			'id' => $prefix . 'building_taxonomy_select',
			'taxonomy' => 'building', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
		array(
			'name' => 'Seating Capacity',
			'desc' => '',
			'id' => $prefix . 'seating_capacity_taxonomy_select',
			'taxonomy' => 'seating-capacity', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
		array(
			'name' => 'Classroom Style',
			'desc' => '',
			'id' => $prefix . 'classroom_style_taxonomy_select',
			'taxonomy' => 'classroom-style', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
		array(
			'name' => 'Installed Hardware',
			'desc' => '',
			'id' => $prefix . 'installed_hardware_taxonomy_select',
			'taxonomy' => 'installed-hardware', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
		array(
			'name' => 'Specialized Hardware',
			'desc' => '',
			'id' => $prefix . 'specialized_hardware_taxonomy_select',
			'taxonomy' => 'specialized-hardware', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
		array(
			'name' => 'Other Features',
			'desc' => '',
			'id' => $prefix . 'other_features_taxonomy_select',
			'taxonomy' => 'other-feature', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
		array(
			'name' => 'Installed Software',
			'desc' => '',
			'id' => $prefix . 'installed_software_taxonomy_select',
			'taxonomy' => 'installed-software', //Enter Taxonomy Slug
			'type' => 'taxonomy_select',	
		),
	),
	);
	return $meta_boxes;
 }
add_filter( 'cmb_meta_boxes' , 'classroom_create_metaboxes' );
 
/**
 * Initialize Metabox Class
 * see /lib/metabox/example-functions.php for more information
 *
 */ 
function classroom_initialize_cmb_meta_boxes() {
    if ( !class_exists( 'cmb_Meta_Box' ) ) {
        require_once( CHILD_DIR . '/lib/metabox/init.php' );
    }
}
add_action( 'init', 'classroom_initialize_cmb_meta_boxes', 9999 );

/** Create a custom taxonomy with $name for $post_type */
function it_add_taxonomy( $name, $post_type ) {
	$name = strtolower( $name );
	add_action( 'init', function() use( $name, $post_type ) {
		$upper = ucwords( $name );
		
		$labels = array(
					'name' => _x( "$upper".'s', 'taxonomy general name' ),
			    'singular_name' => _x( "$upper", 'taxonomy singular name' ),
			    'search_items' =>  __( "Search $upper".'s' ),
			    'popular_items' => __( "Popular $upper".'s' ),
			    'all_items' => __( "All $upper".'s' ),
			    'parent_item' => null,
			    'parent_item_colon' => null,
			    'edit_item' => __( "Edit $upper" ), 
			    'update_item' => __( "Update $upper" ),
			    'add_new_item' => __( "Add New $upper" ),
			    'new_item_name' => __( "New $upper Name" ),
			    'separate_items_with_commas' => __( "Separate $name".'s'." with commas" ),
			    'add_or_remove_items' => __( "Add or remove #name".'s' ),
			    'choose_from_most_used' => __( "Choose from the most used $name".'s' ),
			    'menu_name' => __( "$upper" ),
			);
			
			register_taxonomy(
				$name,
				$post_type,
				array(
					'hierarchival' => false,
					'labels' => $labels,
					'query_var' => true,
					'rewrite' => array( 'slug' => "$name", 'with_front' => false),
				)
			);
		}, 0
	);
}

it_add_taxonomy( 'building', 'classroom' );
it_add_taxonomy( 'seating-capacity', 'classroom' );
it_add_taxonomy( 'classroom-style', 'classroom' );
it_add_taxonomy( 'installed-hardware', 'classroom' );
it_add_taxonomy( 'specialized-hardware', 'classroom' );
it_add_taxonomy( 'other-feature', 'classroom' );
it_add_taxonomy( 'installed-software', 'classroom' );

/** Remove the role taxonomy from the classroom post type screen */
add_action( 'admin_menu', 'remove_custom_taxonomy' );	
	function remove_custom_taxonomy() {
		remove_meta_box( 'tagsdiv-building', 'classroom', 'side' );
		remove_meta_box( 'tagsdiv-seating-capacity', 'classroom', 'side' );
		remove_meta_box( 'tagsdiv-classroom-style', 'classroom', 'side' );
		remove_meta_box( 'tagsdiv-installed-hardware', 'classroom', 'side' );
		remove_meta_box( 'tagsdiv-specialized-hardware', 'classroom', 'side' );
		remove_meta_box( 'tagsdiv-other-feature', 'classroom', 'side' );
		remove_meta_box( 'tagsdiv-installed-software', 'classroom', 'side' );
	}
?>