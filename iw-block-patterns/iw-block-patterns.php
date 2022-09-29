<?php
/*
Plugin Name: Inside Wooster Block Patterns
Description: Adds custom block patterns to the block editor.
Version: 1.0
Author: Jon Breitenbucher
Author URI: https://technology.wooster.edu
*/
function iw_patterns_register_pattern_categories() {
  register_block_pattern_category(
      'wooster',
    array( 'label' => __( 'Wooster Patterns', 'iw-patterns' ) )
      );
}
add_action( 'init', 'iw_patterns_register_pattern_categories' );

function iw_custom_block_patterns()
{
    register_block_pattern(
        'iw-patterns/iw-staff-member-column',
        array(
            'title'       => __('Staff Member Column', 'staff-member-column'),
 
            'description' => _x('Includes an image block, header block, and paragraph block arranged vertically.', 'Block pattern description', 'iw-patterns'),
 
            'content'     => '<!-- wp:group --><div class="wp-block-group"><!-- wp:image {"id":63,"sizeSlug":"large","linkDestination":"media"} --><figure class="wp-block-image size-large"><a href="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg'). '"><img src="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg').'" alt="" class="wp-image-63"/></a></figure><!-- /wp:image --><!-- wp:heading {"fontSize":"x-large"} --><h2 class="has-x-large-font-size"><a href="#">Name</a></h2><!-- /wp:heading --><!-- wp:heading {"level":3,"fontSize":"large"} --><h3 class="has-large-font-size">Title</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Info</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
 
            'categories'  => array('wooster'),
        )
    );
    
    register_block_pattern(
        'iw-patterns/iw-staff-member-row',
        array(
            'title'       => __('Staff Member Row', 'staff-member-row'),
 
            'description' => _x('Includes a column block with 30%/70% columns, image block in left column, and header block and paragraph block in the right column.', 'Block pattern description', 'iw-patterns'),
 
            'content'     => '<!-- wp:group --><div class="wp-block-group"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} --><div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:image {"id":77,"sizeSlug":"full","linkDestination":"media"} --><figure class="wp-block-image size-full"><a href="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg'). '"><img src="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg').'" alt="" class="wp-image-77"/></a></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:heading {"fontSize":"x-large"} --><h2 class="has-x-large-font-size"><a href="#">Name</a></h2><!-- /wp:heading --><!-- wp:heading {"level":3,"fontSize":"large"} --><h3 class="has-large-font-size">Title</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Information</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->',
 
            'categories'  => array('wooster'),
        )
    );

    register_block_pattern(
        'iw-patterns/iw-schedule-mwf-week',
        array(
            'title'       => __('MWF Week', 'schedule-mwf-week'),
 
            'description' => _x('Includes an image block, row blocks, header blocks, and paragraph blocks arranged in a group to allow an individual to create a MWF weekly course schedule one week at a time.', 'Block pattern description', 'iw-patterns'),
 
            'content'     => '<!-- wp:group {"tagName":"section"} --><section class="wp-block-group"><!-- wp:heading {"fontSize":"x-large"} --><h2 class="has-x-large-font-size" id="week1">Week</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"25%"} --><div class="wp-block-column" style="flex-basis:25%"><!-- wp:image {"id":77,"sizeSlug":"full","linkDestination":"media"} --><figure class="wp-block-image size-full"><a href="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg'). '"><img src="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg'). '" alt="" class="wp-image-77"/></a></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {"width":"75%"} --><div class="wp-block-column" style="flex-basis:75%"><!-- wp:heading {"level":3,"fontSize":"large"} --><h3 class="has-large-font-size">Topic</h3><!-- /wp:heading --><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Description</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:group --><div class="wp-block-group"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":4} --><h4>Day</h4><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:heading {"level":4} --><h4>Before class</h4><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:heading {"level":4} --><h4>Assignments</h4><!-- /wp:heading --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":5} --><h5>M</h5><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Class prep/activities</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Assignments for the day</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:columns {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"backgroundColor":"background"} --><div class="wp-block-columns has-background-background-color has-background" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":5} --><h5>W</h5><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Class prep/activities</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Assignments for the day</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":5} --><h5>F</h5><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Class prep/activities</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Assignments for the day</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group --><!-- wp:separator {"className":"is-style-wide"} --><hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/><!-- /wp:separator --></section><!-- /wp:group -->',
 
            'categories'  => array('wooster'),
        )
    );

    register_block_pattern(
        'iw-patterns/iw-schedule-tr-week',
        array(
            'title'       => __('TR Week', 'schedule-tr-week'),
 
            'description' => _x('Includes an image block, row blocks, header blocks, and paragraph blocks arranged in a group to allow an individual to create a TR weekly course schedule one week at a time.', 'Block pattern description', 'iw-patterns'),
 
            'content'     => '<!-- wp:group {"tagName":"section"} --><section class="wp-block-group"><!-- wp:heading {"fontSize":"x-large"} --><h2 class="has-x-large-font-size" id="week1">Week</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"25%"} --><div class="wp-block-column" style="flex-basis:25%"><!-- wp:image {"id":77,"sizeSlug":"full","linkDestination":"media"} --><figure class="wp-block-image size-full"><a href="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg'). '"><img src="'. esc_url( plugin_dir_url(__FILE__).'/images/squirrel.jpg'). '" alt="" class="wp-image-77"/></a></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {"width":"75%"} --><div class="wp-block-column" style="flex-basis:75%"><!-- wp:heading {"level":3,"fontSize":"large"} --><h3 class="has-large-font-size">Topic</h3><!-- /wp:heading --><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Description</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:group --><div class="wp-block-group"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":4} --><h4>Day</h4><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:heading {"level":4} --><h4>Before class</h4><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:heading {"level":4} --><h4>Assignments</h4><!-- /wp:heading --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":5} --><h5>T</h5><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Class prep/activities</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Assignments for the day</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:columns {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"backgroundColor":"background"} --><div class="wp-block-columns has-background-background-color has-background" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"width":"12%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:12%"><!-- wp:heading {"level":5} --><h5>R</h5><!-- /wp:heading --></div><!-- /wp:column --><!-- wp:column {"width":"45%","layout":{"wideSize":""}} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Class prep/activities</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Assignments for the day</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group --><!-- wp:separator {"className":"is-style-wide"} --><hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/><!-- /wp:separator --></section><!-- /wp:group -->',
 
            'categories'  => array('wooster'),
        )
    );

    register_block_pattern(
        'iw-patterns/iw-job-post',
        array(
            'title'       => __('Job Post', 'job-post'),
 
            'description' => _x('Includes a heading block, paragraph block, separator block, and spacer block arranged in a group to allow an individual to quickly add job postings.', 'Block pattern description', 'iw-patterns'),
 
            'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2>Job title</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Description</p><!-- /wp:paragraph --><!-- wp:separator {"className":"is-style-wide"} --><hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/><!-- /wp:separator --><!-- wp:spacer {"height":"10px"} --><div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --></div><!-- /wp:group -->',
 
            'categories'  => array('wooster'),
        )
    );
}
add_action('init', 'iw_custom_block_patterns');
?>