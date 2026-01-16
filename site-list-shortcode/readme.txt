=== Site List ===
Contributors: wooster
Tags: multisite, network, shortcode
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate an auto-updating list of public sites in a WordPress multisite network via a shortcode.

== Description ==

Site List is a small multisite helper plugin intended for network admins.

It registers a shortcode that outputs a table of all public, non-spam, non-deleted, non-archived sites in the current network, including the display names of users who have either the Editor role or the configured Trusted Editor role.

The plugin is marked as Network: true (network-only), so it is only activatable by super admins.

Shortcode: [site-list]

== Installation ==

1. Upload the plugin folder to your /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen.
2. Network activate the plugin.
3. By default, the shortcode is only registered on the main site (blog_id = 1). Add the shortcode [site-list] to a page on the main site.

== Frequently Asked Questions ==

= Can non-super-admins see the list? =

Yes. The shortcode output is intended to be public. Only activation is restricted (network-only plugin).

= Can I hide the main site? =

Yes. Use: [site-list show_main="0"]

= Can I exclude specific sites? =

Yes. Provide a comma-separated list of blog IDs.

Example: [site-list exclude="3,5"]

= Does it cache results? =

Yes. Results are cached using the object cache for 10 minutes by default. You can change this with the cache attribute in seconds.

Example: [site-list cache="120"]

== Changelog ==

= 1.1.2 =
* Add shortcode attribute `exclude` to omit specific sites by blog ID (e.g., exclude="3,5").

= 1.1.1 =
* Combine Editors and Trusted Editors into a single "Editors" column (no role distinction in output).

= 1.1.0 =
* Change shortcode output to an HTML table.
* Add Editors and Trusted Editors columns (trusted role defaults to `trusted_editor`).
* Add shortcode attribute `trusted_role` and filter `cow_site_list_trusted_editor_role`.

= 1.0.3 =
* Register the shortcode only on the main site (blog_id = 1) by default, even when network activated.
* Add filter `cow_site_list_allowed_blog_ids` to allow shortcode registration on additional sites when desired.

= 1.0.2 =
* Make shortcode output public (activation remains network-only).

= 1.0.1 =
* Replace direct database query with get_sites().
* Add object-cache caching for the computed site list.
* Add direct-file-access protection.
* Escape output for safer HTML.
* Add plugin readme.txt.

= 1.0 =
* Initial release.
