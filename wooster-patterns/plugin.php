<?php
/**
 * Plugin Name:       Wooster Patterns
 * Description:       A collection of custom WordPress block patterns for the College of Wooster community.
 * Version:           1.0
 * Author:            Dr. Jon Breitenbucher
 * Author URI:        https://technology.wooster.edu

 */

register_block_pattern(
    'wooster-patterns/first-block-pattern',
    array(
        'title'       => __( 'Block Pattern Name', 'wooster-patterns' ),
        'description' => _x( 'Describe the pattern.', 'Block pattern description', 'wooster-patterns' ),
        'content'     => " ",
    )
);