<?php
/*
Plugin Name: Site List
Plugin URI: https://technology.wooster.edu
Description: This plugin will add a shortcode that can be used to generate and display a list of all the sites in a multisite network.
Author: Jon Breitenbucher
Author URI: http://jon.breitenbucher.net

Version: 1.0

License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/**
 * Add a [site-list] shortcode to list all sites in a multisite network.
 *
 */
function jb_list_all_network_sites()
{
    global $wpdb;

    $result = '';
    $sites = array();
    $blogs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE spam = '0' AND deleted = '0' and archived = '0' and public='1'"));

    if(!empty($blogs))
    {
        foreach($blogs as $blog)
        {
            $details = get_blog_details($blog->blog_id);

            if($details != false)
            {
                $url = $details->siteurl;
                $name = $details->blogname;

                if(!(($blog->blog_id == 1) && ($show_main != 1)))
                {
                    $sites[$name] = $url;
                }
            }
        }

        ksort($sites);

        $count = count($sites);
        $current = 1;
        
        $result.= '<ul class="inside-site-list">';
        foreach($sites as $name=>$url)
        {
            $result.= '<li class="inside-site"><a href="'.$url.'">'.$name.'</a></li>';

            ++$current;
        }
        $result.= '</ul>';
    }

    return $result;
}
add_shortcode('site-list', 'jb_list_all_network_sites');

?>