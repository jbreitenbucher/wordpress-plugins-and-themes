<?php
/**
 * Plugin Name: LDAP Authentication Plug-in
 * Description: A complete moderinization to WordPress 6.9+ and PHP 8.4+ of a plugin originally developed by
 * Aaron Axelsen, Sean Wedig, Dexter Arver, Alex Barker, Hugo Salgado, Patrick Cavit, and Alistair Young.
 * The plugin to overrides WordPress authentication using an LDAP server (plus optional SSO), with multisite
 * provisioning and admin tools.
 * Version: 4.0.0
 * Author: The College of Wooster
 * Requires at least: 6.9
 * Requires PHP:      8.3
 * License:           GPL-2.0-or-later
 * Text Domain: ldap-auth
 * Domain Path: /languages
*/

defined('ABSPATH') || exit;

// Keep an easy, stable version constant for internal use.
if (!defined('WOOSTER_LDAP_AUTH_VERSION')) {
    define('WOOSTER_LDAP_AUTH_VERSION', '4.0.0');
}

// Autoload + bootstrap.
require_once __DIR__ . '/includes/Support/Autoload.php';

add_action('plugins_loaded', static function (): void {
    \Wooster\LdapAuth\Plugin::instance(__FILE__)->init();
}, 5);
