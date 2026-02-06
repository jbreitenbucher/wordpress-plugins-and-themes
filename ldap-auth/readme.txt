=== LDAP Authentication Plug-in ===
Contributors: wooster
Tags: ldap, authentication, sso, multisite
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 4.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Override WordPress authentication using an LDAP directory (optionally with SSO), with multisite provisioning and admin tools.

== Description ==

This plugin replaces (or supplements) WordPress username/password authentication with LDAP-backed authentication.
It is intended for institutional environments (campus / enterprise directories) and supports WordPress Multisite.

Key capabilities:

* Authenticate users against an LDAP directory.
* Optional SSO integration (where configured).
* Multisite-friendly user provisioning and management helpers.
* Admin configuration UI and diagnostics.

== Installation ==

1. Upload the plugin folder to your WordPress installation:
   * Network: wp-content/plugins/ldap-auth/
2. Activate the plugin.
3. Configure LDAP connection settings in the plugin’s settings screen.
4. Test authentication using a known directory account before enabling it broadly.

== Frequently Asked Questions ==

= Does this plugin support PHP 8.3+? =
Yes. The plugin targets PHP 8.3 and later.

= Does this plugin work on Multisite? =
Yes. It is designed to work in Multisite environments.

= What LDAP servers does it support? =
Any LDAPv3-compatible directory should work (for example, Microsoft Active Directory), provided the connection and attribute mappings are configured correctly.

== Changelog ==

= 4.0.0 =
* Modernized for WordPress 6.9+ and PHP 8.3+.

