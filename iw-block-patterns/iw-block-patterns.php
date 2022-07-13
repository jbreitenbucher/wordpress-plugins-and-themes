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
 
 
 
            'content'     => "<!-- wp:group -->\r\n<div class=\"wp-block-group\"><!-- wp:image {\"id\":63,\"sizeSlug\":\"large\",\"linkDestination\":\"media\"} -->\r\n<figure class=\"wp-block-image size-large\"><a href=\"http:\/\/collegeofwooster.net\/wordpresscore\/wp-content\/uploads\/2022\/07\/squirrel.jpg\"><img src=\"http:\/\/collegeofwooster.net\/wordpresscore\/wp-content\/uploads\/2022\/07\/squirrel-edited.jpg\" alt=\"\" class=\"wp-image-63\"/></a></figure>\r\n<!-- /wp:image -->\r\n\r\n<!-- wp:heading {\"fontSize\":\"x-large\"} -->\r\n<h2 class=\"has-x-large-font-size\"><a href=\"\#\">Name</a></h2>\r\n<!-- /wp:heading -->\r\n\r\n<!-- wp:heading {\"level\":3,\"fontSize\":\"large\"} -->\r\n<h3 class=\"has-large-font-size\">Title</h3>\r\n<!-- /wp:heading -->\r\n\r\n<!-- wp:paragraph -->\r\n<p>Info</p>\r\n<!-- /wp:paragraph --></div>\r\n<!-- /wp:group -->",
 
 
            'categories'  => array('wooster'),
        )
    );
    
    register_block_pattern(
        'iw-patterns/iw-staff-member-row',
        array(
            'title'       => __('Staff Member Row', 'staff-member-row'),
 
            'description' => _x('Includes a column block with 30%/70% columns, image block in left column, and header block and paragraph block in the right column.', 'Block pattern description', 'iw-patterns'),
 
 
 
            'content'     => "<!-- wp:group -->\r\n<div class=\"wp-block-group\"><!-- wp:columns -->\r\n<div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"33.33%\"} -->\r\n<div class=\"wp-block-column\" style=\"flex-basis:33.33%\"><!-- wp:image {\"id\":63,\"sizeSlug\":\"full\",\"linkDestination\":\"media\"} -->\r\n<figure class=\"wp-block-image size-full\"><a href=\"http:\/\/collegeofwooster.net\/wordpresscore\/wp-content\/uploads\/2022\/07\/squirrel-edited.jpg\"><img src=\"http:\/\/collegeofwooster.net\/wordpresscore\/wp-content\/uploads\/2022\/07\/squirrel-edited.jpg\" alt=\"\" class=\"wp-image-63\"/></a></figure>\r\n<!-- /wp:image --></div>\r\n<!-- /wp:column -->\r\n\r\n<!-- wp:column {\"width\":\"66.66%\"} -->\r\n<div class=\"wp-block-column\" style=\"flex-basis:66.66%\"><!-- wp:heading {\"fontSize\":\"x-large\"} -->\r\n<h2 class=\"has-x-large-font-size\"><a href=\"#\">Name</a></h2>\r\n<!-- /wp:heading -->\r\n\r\n<!-- wp:heading {\"level\":3,\"fontSize\":\"large\"} -->\r\n<h3 class=\"has-large-font-size\">Title</h3>\r\n<!-- /wp:heading -->\r\n\r\n<!-- wp:paragraph -->\r\n<p>Info</p>\r\n<!-- /wp:paragraph --></div>\r\n<!-- /wp:column --></div>\r\n<!-- /wp:columns --></div>\r\n<!-- /wp:group -->",
 
 
            'categories'  => array('wooster'),
        )
    );
}
add_action('init', 'iw_custom_block_patterns');
?>