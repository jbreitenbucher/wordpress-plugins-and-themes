<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

$nbtpl_plugin_dir = plugin_dir_path( __FILE__ );

require_once( $nbtpl_plugin_dir . 'blogtemplates.php' );

$nbtpl_model = nbtpl_get_model();
$nbtpl_model->delete_tables();

delete_site_option( 'nbt_plugin_version' );
delete_site_option( 'blog_templates_options' );