<?php
/*
Plugin Name: Sort "My Sites"
Description: Sorts the My Sites listing on both the page and in the 3.3 admin bar dropdown
Author: Otto
*/

add_filter('get_blogs_of_user','sort_my_sites');
function sort_my_sites($blogs) {
        $f = create_function('$a,$b','return strcasecmp($a->blogname,$b->blogname);');
        uasort($blogs, $f);
        return $blogs;
}
?>