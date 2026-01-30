<?php
/*
Plugin Name: LDAP Authentication Plug-in
Plugin URI: http://wpmuldap.tuxdocs.net
Description: A plugin to override the core Wordpress MU authentication method so as to use an LDAP server for authentication.  
Version: 3.1.1
Author: Aaron Axelsen (http://www.frozenpc.net)
	Sean Wedig (http://www.thecodelife.net) 
	Dexter Arver
	Alex Barker (http://www.callutheran.edu)
	Hugo Salgado (http://hugo.vulcano.cl)
	Patrick Cavit (http://patcavit.com)
	Alistair Young (http://www.weblogs.uhi.ac.uk/sm00ay/)
*/

// Includes
require_once("ldap/lib/ldap_ro.php");

// methods for supporting site-admin configuration of the plugin
require_once("ldap/lib/wpmu_ldap_admin.functions.php");
require_once("ldap/lib/wpmu_ldap_adduser.functions.php");

add_action('admin_init', 'ldap_addstylesheet');
add_action('network_admin_menu', 'ldap_addmenu');
add_action('admin_menu', 'ldap_addmenuuser');
add_action('network_admin_menu', 'ldap_addmenuuser');
add_action('admin_menu', 'wpmuRemoveAddNewMenu');
add_action('network_admin_menu', 'wpmuRemoveAddNewMenu');

define('LDAP_DEBUG_MODE',false);

// perform these filters, actions, and WP function overrides only if LDAP-
//  authentication is enabled; this is to cut down on parsing of this code when
//  it doesn't apply 
if (get_site_option("ldapAuth")) { 

	// Add radio buttons for switching individual users between LDAP accounts and non-LDAP accounts
	add_action('edit_user_profile', 'wpmuUserFormLdapOption');
	add_action('edit_user_profile_update', 'wpmuUserFormLdapOptionUpdate');
	add_action('show_user_profile', 'wpmuUserFormLdapOption');
	add_action('personal_options_update', 'wpmuUserFormLdapOptionUpdate');

	// *** End Admin Config Functions *** //

	// *** Begin User Auth Functions *** //
	// disable public signup if configured to do so  
	if (get_site_option('ldapDisableSignup')) {
		add_action('signup_header', 'wpmuLdapDisableSignup');
		add_action('login_head', 'wpmuLdapDisableSignupMessage');
	}

	// only include them if it's active, so as to cut down on continual parsing of the code 
	require_once("ldap/lib/wpmu_ldap.functions.php");

	// Authentication filters
	add_action('authenticate', 'wpmuLdapUsernamePasswordAuthenticate', 25, 3);
	add_filter('wp_authenticate_user', 'wpmuLdapCheckLdapMeta'); //disabled local login if ldap meta flag is set
	if (get_site_option('ldapSSOEnabled')) {
		add_action('authenticate', 'wpmuLdapSSOAuthenticate', 40, 3);
		add_filter('login_url', 'wpmuLdapSSODisableReauth'); //removes reauth from login URL
	}

	// disable only for ldap accounts
	add_filter('show_password_fields', 'wpmuLdapDisableLdapPassword');

	// disable default add user box	
	add_filter('show_adduser_fields', 'wpmuLdapDisableShowUser');

}
