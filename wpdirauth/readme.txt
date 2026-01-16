=== wpDirAuth ===
Contributors: gilzow, stephdau, apokalyptik
Tags: login, authentication, directory, ldap, ldaps
Requires at least: 2.2
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.10.7
WordPress directory authentication plugin through LDAP and LDAPS (SSL).
== Description ==
Please see the Changelog (Development tab above) for recent updates/changes.

wpDirAuth shifts authentication from the local WordPress instance to a central directory (LDAP) server(s).

wpDirAuth allows users of central directory (LDAP) servers to login to authorized WordPress instances without having to register. The plugin creates a new account for each directory user on first login so that they have full access to preferences and functions, as any WP user would. Activating the plugin will not restrict you to using directory authentication and you will still be able to both create new WP-only users as well as turn on public registration in WordPress. You can also assign any privilege levels to your directory users, and the those users will be referred to their institutional password policy whenever they would normally able to update their WP passwords (on the profile screen, in user edit, etc).

= LDAP/LDAPS =
Authentication should work with most LDAP enabled directory services, such as OpenLDAP, Apache Directory, Microsoft Active Directory, Novell eDirectory, Sun Java System Directory Server, and more. wpDirAuth supports LDAP and LDAPS (SSL) connectivity and can force SSL for WordPress authentication if it is available on the Web server. It also supports server connection pools, for pseudo load balancing and fault tolerance, or multiple source directory authentication. Because the key used to locate a user's profile in the LDAP server is not always the same, depending on your LDAP server type and institutional choices, you can define your own through the wpDirAuth administration tool. When logging in as a directory user, the WP "remember me" feature is downgraded from 6 months for regular WP users to only 1 hour, so that institutional passwords are not overly endangered when accessing WP from public terminals.

= Branding & Notifications =
You can define notifications addressed to your directory users in key WordPress areas, such as the login screen and the profile edit screen. Since these admin-editable values support HTML (admin, coders, beware of xss!), you can point your directory users to central support information related to functions such as changing their institutional password, a WordPress usage related policy, etc. There is also a simple and optional terms of services concept, only implemented for directory users, which will simply record a one-time acceptance date when agreed upon. Note that agreeing to the TOS has no effect on the user's level of access in the system, fact which could change in future version if there is a demand for it, or through direct code contribution to that effect.

== Installation ==
Installing should be a piece of cake and take fewer than ten minutes, provided you know your directory server(s) information, and that your blog is allowed to connect and bind to it/them. Please refer to your friendly neighbourhood LDAP sysadmin for more information. Or use a LDAP browser (e.g. Apache Directory Studio) to test/research what your specific connections settings need to be.

1. Upload the `wpDirAuth` directory to the `/wp-content/plugins/` directory.
2. Login to your WordPress instance as an admin user.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to the `wpDirAuth` menu found in the WordPress `Settings` section.
5. Enter your directory server(s) information, Bind DN + password and set your preferences.
You should now be able to login as a directory user.

== Using wpDirAuth ==
Once installed and activated, you will be able to administer your directory settings through the dedicated plugin configuration tool found under the `wpDirAuth` menu found in the WordPress `Settings` admin section. Directory Authenticated users can now be pre-added to your wordpress system and granted roles by going to the `Add Dir Auth User` menu found in the Wordpress `Users` admin section. Contextual help for this section is available for this section within Wordpress' built-in help menu. See the inline help found in the tool for more information on the settings. There is a secondary activation toggle, so you can install and activate the plugin, check out the options panel, but not immediately accept directory authentication, or even simply turn the feature on or off at any time.

== Help and Support ==
Please post questions, request for help to the Wordpress plugins forum or email <wpdirauth@gilzow.com>. Please be sure to include 'wpdirauth' in the subject line.

== TO-DO's ==
+ Internationalization
+ Refactor to a class
+ More action/filter hooks

== Source and Development ==
wpDirAuth welcomes friendly contributors wanting to lend a hand, be it in the form of code through SVN patches, user support, platform portability testing, security consulting, localization help, etc. The [current] goal is to keep the plugin self-contained (ie: no 3rd-party lib) for easier security maintenance, while keeping the code clean and extensible. Focus is on security, features, security, and let's not forget, security. Unit tests will hopefully be developed and constant security audit performed. Recurring quality patch contributions will lead to commit privileges to the project source repository. Please post questions/requests for help to the wordpress forums and/or email <wpdirauth@gilzow.com>

== License ==
[General Public License](http://www.gnu.org/licenses/gpl.html)
Copyrights are listed in chronological order, by contributions.
wpDirAuth: WordPress Directory Authentication, original author
Copyright (c) 2007 Stephane Daury - http://stephane.daury.org/
wpDirAuth and wpLDAP Patch Contributions
Copyright (c) 2007 PKR Internet, LLC - http://www.pkrinternet.com/

wpDirAuth Patch Contributions
Copyright (c) 2007 Todd Beverly
wpLDAP: WordPress LDAP Authentication
Copyright (c) 2007 Ashay Suresh Manjure - http://ashay.org/
wpDirAuth Patch Contribution and current maintainer
Copyright (c) 2010-2017 Paul Gilzow - http://gilzow.com/
wpDirAuth is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.
wpDirAuth is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.

== Project History ==
Originally started from a patched version of wpLDAP (1.02+patch), wpDirAuth has
since then been heavily overhauled and features have been modified and added.
In other words, a classic case of `pimp my lib'` (hopefully for the better).
* Current: wpDirAuth: <http://wpdirauth.gilzow.com/>
* Original: wpLDAP: <https://wordpress.org/plugins/wpldap/>
* wpLDAP Patch: <https://web.archive.org/web/20100731020249/http://www.pkrinternet.com/~rbulling/private/wpLDAP-1.02-ssl.patch>

== Changelog ==
= 1.10.7 =
+ Bug fix: Corrects an issue with parameter order with implode() for users on PHP v8.0 and above. Apologies for two updates in one day. Big shoutout to https://www.forumsys.com/ for providing a publicly accessible LDAP instance for testing purposes.
= 1.10.6 =
+ Bug Fix: Reset Password button was showing up in ad-authenticated users' profile due to a removed global variable, that was removed starting at WordPress v6. Huge shout-out to user @silsbyc for assisting me in troubleshooting this one.
= 1.10.5 =
+ Bug Fix: LDAP properties for first/last name were reversed. Credit to @mradojevic for catching that.
= 1.10.4 =
+ Bug Fix: In multisite, when adding a user from the network admin panel, generated email would always link to https instead of the adhering to the option Require SSL Login.
= 1.10.3. =
+ minor bug fix. Was missing user id when removing old password
= 1.10.2 =
+ fix bug where array of properties passed to ldap_search was in the wrong format.
= 1.10.1 =
+ Added filter `wpdirauth_ldap_user_keys`
+ removed call to deprecated function `force_ssl_login()`
= 1.9.4 =
+ Correction to fix incompatibility with a couple of other plugins
= 1.9.3 =
+ Action/Filter hooks now available. Documentation: http://www.gilzow.com/_wpdirauth/hooks/
+ Cookie time-out. The authentication cookie's time-out can now be set in the settings panel. Time entered shoudl be in hours. Default is still one hour.
+ Added PHP 5.2.X support (broken in previous versions), but this will likely be the last version that supports PHP versions less than 5.3
+ CSS Changes in the settings area
+ Fixed bug from 1.8.1 that allowed certain AD-authed users to still authenticate when directory authentication was disabled
= 1.8.1 =
+ I had forgotten that wp-login.php, for some odd reason, triggers the login/authenticate functions/filters when a user arrives to the page via GET method, even when the action parameter isn't set. This was causing wpDirAuth to generate a WP_Error object when a user initially visited the page. If you were using another plugin that records login failures (e.g. anti Brute Force) this would result in two failed attempts for every single actual failed attempt.  Or if you are monitoring for failed attempts (e.g. Sucuri, iThemes Security) then you would receive false warnings of a failed login attempt using an "Unknown" username.
+ Fix more typos
+ Clean up the README cuz goodness it was a mess
= 1.8.0 =
+ wpDirAuth now uses the `authenticate` filter hook instead of overriding wp_authenticate() in pluggable.php.  This should increase its compatibility with other plugins that hook `authenticate`. HOWEVER, if you have another plugin that overrides wp_authenticate and doesn't apply the `authenticate` filter, wpDirAuth will not work.
+ Changed the Settings menu name from `Directory Auth.` to `wpDirAuth`
+ Removed a possible XSS / Redirection vulnerability if SSL Required was set to  true in wpDirAuth, the user visited the non-ssl'ed version of a site *and* an attacker injected/spoofed the HTTP_HOST header.
+ Prevent direct file access
+ Fixed a couple of typos
+ Updated license to GPLv2 or later
+ Changed the Safe Mode error messages to better explain the issues
= 1.7.17 =
+ bugfix - a local user account on systems using PHP version < 5.3.9 would fail authentication, even if correct password was used. Many thanks to @tommcgee for helping me track this one down.
= 1.7.16 =
+ minor bugfixes that were causing PHP warning errors in specific situations.
= 1.7.15 =
+ let's try this again. filter wpdirauth_filterquery will pass THREE parameters: current AD filter, the account filter as set in the wpDirAuth settings and the username of the person attempting to authenticate. Callback function must pass back a valid AD filter.
= 1.7.14 =
+ Why no 1.7.13? Because I'm superstitious.
+ Added hookable filter wpdirauth_filterquery. Will pass 2 parameters: the current AD filter and the username of the person attempting to authenticate. Callback function must pass back a valid AD filter.
+ v4.4.2 of wordpress changed some of the roles & caps. Role create_users no longer allows admins who do not posses super-admin privilege in a multisite to see the wpDirAuth add user menu item when in a site.  changed the cap to add_users, but will continue to research
= 1.7.12 =
missed an instance of split (deprecated php function).
= 1.7.11 =
Adding stripslashes_deep because wordpress.
= 1.7.10 =
Bug fixes and minor clean-up
= 1.7.9 =
* The plugin no longer automatically creates accounts for directory-authenticated users who log into the site.  You can enable this behavior in the plugin settings, but it is no longer the default behavior.
* Not sure why but I never set the plugin up to block Directory-Authenticated users from attempting to use the wordpress builtin password reset tool.  It can't actually change the password, but it definitely caused confusion among users.  wpDirAuth now blocks directory-authenticated users from using the password reset tool.
* Removed a bunch of deprecated function calls.
* Cleaned up some of the debugging messages.
= 1.7.6 =
Corrected situation where a new authenticated user logging into a child site in a multisite network was added to the parent site, instead of the child site where they initiated the login. Also, somewhere along the way, I reintroduced a bug that when using authentication groups, the plugin would fail to redirect a successfully logged in user.
= 1.7.5 =
* PLEASE NOTE Beta testers of the 1.7.X branch prior to version 1.7.5, you will need to deactivate wpdirauth before you updgrade to this latest version. Once you have installed and network activated the plugin, it will copy your options from their previous location to the sitemeta table. You will only need to do this once. This will also work for anyone who was using the 1.6.X branch or older and plans on using it in MULTISITE mode.
* MULTISITE support, bug fixes, security enhancements
= 1.6.1 =
* Corrected a bug that would prevent user profiles from successfully being found. Thanks go to jgiangrande for identifying the problem area.
= 1.6.0 =
* Added `Add Dir Auth User` to Admin User menu. Now able to pre-add Directory Authenticated users and assign roles where previously users would have to log in first, and then have an admin change their role.
= 1.5.2 =
* Added ability to limit logins to specific AD groups. Fixed a bug that produced an incorrect filter when using a single Authentication Group
= 1.5.1 =
* Remove default password nag for wpdirauth accounts
