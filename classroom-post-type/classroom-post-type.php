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
		'supports' => array('thumbnail','title'),
		'rewrite' => array('slug' => 'classroom'),
		'has_archive' => 'classrooms',
	);
	register_post_type('itclassroom',$args);
}

/** Customize the icon for the classroom post type */
add_action('admin_head', 'set_classroom_icon');
function set_classroom_icon() {
	global $post_type;
	?>
	<style>
	<?php if (($_GET['post_type'] == 'itclassroom') || ($post_type == 'itclassroom')) : ?>
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
		remove_post_type_support('itclassroom', 'editor');
	}	

/** Create a custom taxonomies for classroom post type */
add_action( 'init', 'it_add_taxonomies', 0 );
function it_add_taxonomies(){
	$labels_building = array(
	    'name' => _x( 'Buildings', 'taxonomy general name' ),
	    'singular_name' => _x( 'Building', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Buildings' ),
	    'popular_items' => __( 'Popular Buildings' ),
	    'all_items' => __( 'All Buildings' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Building' ), 
	    'update_item' => __( 'Update Building' ),
	    'add_new_item' => __( 'Add New Building' ),
	    'new_item_name' => __( 'New Building Name' ),
	    'separate_items_with_commas' => __( 'Separate buildings with commas' ),
	    'add_or_remove_items' => __( 'Add or remove buildings' ),
	    'choose_from_most_used' => __( 'Choose from the most used buildings' ),
	    'menu_name' => __( 'Buildings' ),
	  );

	$labels_seating = array(
	    'name' => _x( 'Seating Capacities', 'taxonomy general name' ),
	    'singular_name' => _x( 'Seating Capacity', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Seating Capacities' ),
	    'popular_items' => __( 'Popular Seating Capacities' ),
	    'all_items' => __( 'All Seating Capacities' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Seating Capacity' ), 
	    'update_item' => __( 'Update Seating Capacity' ),
	    'add_new_item' => __( 'Add New Seating Capacity' ),
	    'new_item_name' => __( 'New Seating Capacity Name' ),
	    'separate_items_with_commas' => __( 'Separate seating capacities with commas' ),
	    'add_or_remove_items' => __( 'Add or remove seating capacities' ),
	    'choose_from_most_used' => __( 'Choose from the most used seating capacities' ),
	    'menu_name' => __( 'Seating Capacities' ),
	  );

	$labels_classroom = array(
	    'name' => _x( 'Classroom Types', 'taxonomy general name' ),
	    'singular_name' => _x( 'Classroom Type', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Classroom Types' ),
	    'popular_items' => __( 'Popular Classroom Types' ),
	    'all_items' => __( 'All Classroom Types' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Classroom Type' ), 
	    'update_item' => __( 'Update Classroom Type' ),
	    'add_new_item' => __( 'Add New Classroom Type' ),
	    'new_item_name' => __( 'New Role Classroom Type' ),
	    'separate_items_with_commas' => __( 'Separate classroom types with commas' ),
	    'add_or_remove_items' => __( 'Add or remove classroom types' ),
	    'choose_from_most_used' => __( 'Choose from the most used classroom types' ),
	    'menu_name' => __( 'Classroom Types' ),
	  );

	$labels_installed_hardware = array(
	    'name' => _x( 'Installed Hardware', 'taxonomy general name' ),
	    'singular_name' => _x( 'Installed Hardware', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Installed Hardware' ),
	    'popular_items' => __( 'Popular Installed Hardware' ),
	    'all_items' => __( 'All Installed Hardware' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Installed Hardware' ), 
	    'update_item' => __( 'Update Installed Hardware' ),
	    'add_new_item' => __( 'Add New Installed Hardware' ),
	    'new_item_name' => __( 'New Installed Hardware Name' ),
	    'separate_items_with_commas' => __( 'Separate installed hardware with commas' ),
	    'add_or_remove_items' => __( 'Add or remove installed hardware' ),
	    'choose_from_most_used' => __( 'Choose from the most used installed hardware' ),
	    'menu_name' => __( 'Installed Hardware' ),
	  );

	$labels_specialized_hardware = array(
	    'name' => _x( 'Specialized Hardware', 'taxonomy general name' ),
	    'singular_name' => _x( 'Specialized Hardware', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Specialized Hardware' ),
	    'popular_items' => __( 'Popular Specialized Hardware' ),
	    'all_items' => __( 'All Specialized Hardware' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Specialized Hardware' ), 
	    'update_item' => __( 'Update Specialized Hardware' ),
	    'add_new_item' => __( 'Add New Specialized Hardware' ),
	    'new_item_name' => __( 'New Specialized Hardware Name' ),
	    'separate_items_with_commas' => __( 'Separate specialized hardware with commas' ),
	    'add_or_remove_items' => __( 'Add or remove specialized hardware' ),
	    'choose_from_most_used' => __( 'Choose from the most used specialized hardware' ),
	    'menu_name' => __( 'Specialized Hardware' ),
	  );

	$labels_other_features = array(
	    'name' => _x( 'Other Features', 'taxonomy general name' ),
	    'singular_name' => _x( 'Other Feature', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Other Features' ),
	    'popular_items' => __( 'Popular Other Features' ),
	    'all_items' => __( 'All Other Features' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Other Feature' ), 
	    'update_item' => __( 'Update Other Feature' ),
	    'add_new_item' => __( 'Add New Other Feature' ),
	    'new_item_name' => __( 'New Other Feature Name' ),
	    'separate_items_with_commas' => __( 'Separate other features with commas' ),
	    'add_or_remove_items' => __( 'Add or remove other features' ),
	    'choose_from_most_used' => __( 'Choose from the most used other features' ),
	    'menu_name' => __( 'Other Features' ),
	  );

	$labels_installed_software = array(
	    'name' => _x( 'Installed Software', 'taxonomy general name' ),
	    'singular_name' => _x( 'Installed Software', 'taxonomy singular name' ),
	    'search_items' =>  __( 'Search Installed Software' ),
	    'popular_items' => __( 'Popular Installed Software' ),
	    'all_items' => __( 'All Installed Software' ),
	    'parent_item' => null,
	    'parent_item_colon' => null,
	    'edit_item' => __( 'Edit Installed Software' ), 
	    'update_item' => __( 'Update Installed Software' ),
	    'add_new_item' => __( 'Add New Installed Software' ),
	    'new_item_name' => __( 'New Installed Software Name' ),
	    'separate_items_with_commas' => __( 'Separate installed software with commas' ),
	    'add_or_remove_items' => __( 'Add or remove installed software' ),
	    'choose_from_most_used' => __( 'Choose from the most used installed software' ),
	    'menu_name' => __( 'Installed Software' ),
	  );

	register_taxonomy(  
    	'building',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_building,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'buildings', 'with_front' => false ),
    		)  
	);

	register_taxonomy(  
    	'seating-capacity',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_seating,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'seating-capacities', 'with_front' => false ),
    		)  
	);

	register_taxonomy(  
    	'classroom-style',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_classroom,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'classroom-styles', 'with_front' => false ),
    		)  
	);

	register_taxonomy(  
    	'installed-hardware',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_installed_hardware,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'installed-hardware', 'with_front' => false ),
    		)  
	);

	register_taxonomy(  
    	'specialized-hardware',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_specialized_hardware,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'specialized-hardware', 'with_front' => false ),
    		)  
	);

	register_taxonomy(  
    	'other-feature',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_other_features,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'other-features', 'with_front' => false ),
    		)  
	);

	register_taxonomy(  
    	'installed-software',  
    	'itclassroom',  
    		array(  
        	'hierarchical' => false,  
        	'labels' => $labels_installed_software,  
        	'query_var' => true,  
        	'rewrite' => array( 'slug' => 'installed-software', 'with_front' => false ),
    		)  
	);
}

/** Remove the role taxonomy from the classroom post type screen */
add_action( 'admin_menu', 'remove_classroom_taxonomy' );	
function remove_classroom_taxonomy() {
	remove_meta_box( 'tagsdiv-building', 'itclassroom', 'side' );
	remove_meta_box( 'tagsdiv-seating-capacity', 'itclassroom', 'side' );
	remove_meta_box( 'tagsdiv-classroom-style', 'itclassroom', 'side' );
	remove_meta_box( 'tagsdiv-installed-hardware', 'itclassroom', 'side' );
	remove_meta_box( 'tagsdiv-specialized-hardware', 'itclassroom', 'side' );
	remove_meta_box( 'tagsdiv-other-feature', 'itclassroom', 'side' );
	remove_meta_box( 'tagsdiv-installed-software', 'itclassroom', 'side' );
}

/**
 * Create a custom Metabox for the classroom post type
 *
 * @link http://www.billerickson.net/wordpress-metaboxes/
 *
 */
add_action( 'cmb_render_taxonomy_multicheck_inline', 'it_cmb_render_taxonomy_multicheck_inline', 10, 2 );
function it_cmb_render_taxonomy_multicheck_inline( $field, $meta ) {
		echo '<ul class="cmb_radio_inline_option">';
		$names = wp_get_object_terms( $post->ID, $field['taxonomy'] );
		$terms = get_terms( $field['taxonomy'], 'hide_empty=0' );
		foreach ($terms as $term) {
			echo '<li class="cmb_radio_inline_option"><input type="checkbox" name="', $field['id'], '[]" id="', $field['id'], '" value="', $term->name , '"'; 
			foreach ($names as $name) {
				if ( $term->slug == $name->slug ){ echo ' checked="checked" ';};
			}
			echo' /><label>', $term->name , '</label></li>';
		}
		echo '</ul>';
}

/**
 * Create a custom metaboxes for the itpeople and itclassroom post types
 *
 * @link http://www.billerickson.net/wordpress-metaboxes/
 *
 */
add_filter( 'cmb_meta_boxes' , 'it_create_metaboxes' );
function it_create_metaboxes( $meta_boxes ) {
	$prefix = 'it_'; // start with an underscore to hide fields from custom fields list
	$meta_boxes[] = array(
		'id' => 'classroom_info_metabox',
		'title' => 'Classroom Information',
		'pages' => array('itclassroom'), // post type
		'context' => 'normal',
		'priority' => 'low',
		'show_names' => true, // Show field names on the left
		'fields' => array(
			array(
				'name' => 'Notes',
				'desc' => 'Give a brief description of the classroom.',
				'id' => $prefix . 'notes_wysiwyg',
				'type' => 'wysiwyg',
				'options' => array(
					'wpautop' => true, // use wpautop?
					'media_buttons' => false, // show insert/upload button(s)
				),
			),
			array(
				'name' => 'Building',
				'desc' => '',
				'id' => $prefix . 'building_taxonomy',
				'taxonomy' => 'building', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
			array(
				'name' => 'Seating Capacity',
				'desc' => '',
				'id' => $prefix . 'seating_capacity_taxonomy',
				'taxonomy' => 'seating-capacity', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
			array(
				'name' => 'Classroom Style',
				'desc' => '',
				'id' => $prefix . 'classroom_style_taxonomy',
				'taxonomy' => 'classroom-style', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
			array(
				'name' => 'Installed Hardware',
				'desc' => '',
				'id' => $prefix . 'installed_hardware_taxonomy',
				'taxonomy' => 'installed-hardware', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
			array(
				'name' => 'Specialized Hardware',
				'desc' => '',
				'id' => $prefix . 'specialized_hardware_taxonomy',
				'taxonomy' => 'specialized-hardware', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
			array(
				'name' => 'Other Features',
				'desc' => '',
				'id' => $prefix . 'other_features_taxonomy',
				'taxonomy' => 'other-feature', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
			array(
				'name' => 'Installed Software',
				'desc' => '',
				'id' => $prefix . 'installed_software_taxonomy',
				'taxonomy' => 'installed-software', //Enter Taxonomy Slug
				'type' => 'taxonomy_multicheck_inline',	
			),
		),
	);
	return $meta_boxes;
}

/**
 * Initialize Metabox Class
 * see /lib/metabox/example-functions.php for more information
 *
 */
add_action( 'init', 'it_initialize_cmb_meta_boxes', 9999 );
function it_initialize_cmb_meta_boxes() {
    if ( !class_exists( 'cmb_Meta_Box' ) ) {
        require_once( plugins_dir_path( __FILE__) . '/lib/metabox/init.php' );
    }
}
?>