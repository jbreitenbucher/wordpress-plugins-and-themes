=== Add Trusted Roles ===
Contributors: wooster
Tags: roles, multisite, security, unfiltered_html
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds "Trusted Editor" and "Trusted Administrator" roles (cloned from the built-in Editor/Administrator roles) and grants those trusted roles the ability to use unfiltered HTML on Multisite.

== Description ==

In WordPress Multisite, the `unfiltered_html` capability is normally restricted. This plugin:

* Creates two additional roles:
  * Trusted Editor (cloned from Editor)
  * Trusted Administrator (cloned from Administrator)
* Removes `unfiltered_html` from the standard Editor and Administrator roles (defense-in-depth).
* Grants `unfiltered_html` to users who have either trusted role via `map_meta_cap`.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install it through the Plugins screen.
2. Activate the plugin through the "Plugins" screen (network-activate if desired).

== Frequently Asked Questions ==

= Does this change the built-in Editor/Administrator capabilities? =

On single-site WordPress, yes: it removes `unfiltered_html` from those roles while the plugin is active, and selectively grants it to users who have a trusted role. On Multisite, those built-in roles typically don’t have `unfiltered_html` anyway, so the plugin doesn’t modify them.

= What if my site doesn’t have those base roles? =

The plugin checks that the base roles exist before cloning capabilities.

== Changelog ==

= 1.1.3 =
* Fix Plugin Check i18n warnings (use literal text domain; remove discouraged textdomain loader).
* Fix a critical recursion bug in option retrieval.

= 1.1.2 =
* Backward-compatible migration for stored option key when renaming plugin prefixes.
* Documentation: clarify single-site vs multisite behavior for built-in roles.

= 1.1.1 =
* In multisite, skip removing/restoring `unfiltered_html` on the built-in Editor/Administrator roles (it is redundant by default).

= 1.1 =
* Add activation/deactivation hooks (including network-wide behavior) to add/remove roles and restore prior `unfiltered_html` state.
* Add multisite new-site support when network-activated.
* Add text domain, translation loading, and i18n-ready role names.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.1 =
Recommended update.

= 1.1 =
Recommended update.

= 1.0 =
Initial release.
