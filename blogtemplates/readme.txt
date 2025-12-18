=== New Blog Templates ===
Contributors: thecollegeofwooster,wpmudev
Tags: multisite, templates
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 3.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create new sites in WordPress Multisite from pre-configured template sites.

== Description ==
New Blog Templates lets network admins create “template” sites, then create new sites that copy content and settings from a chosen template.

This fork is being modernized for PHP 8.3+ and current WordPress versions while preserving the original multisite workflow.

== Installation ==
1. Upload the plugin folder to your network’s plugins directory, or install the ZIP via the Network Admin.
2. Network Activate the plugin.
3. Go to **Network Admin → Blog Templates** to create and manage template sites.

== Frequently Asked Questions ==
= Does this work on a single-site WordPress install? =
No. This plugin requires WordPress Multisite.

= Where is the full legacy changelog? =
The plugin includes a `CHANGELOG` file for extended history and internal phase notes.

== Screenshots ==
1. Network Admin Templates screen.
2. Template selection screen on `wp-signup.php`.

== Changelog ==
= 3.0.4 =
* Enqueue/versioning audit: add explicit $ver (and $in_footer for scripts) to satisfy Plugin Check WP.EnqueuedResourceParameters.
* Standardize plugin constants to NBTPL_* (4+ chars) with deprecated back-compat NBT_* aliases.
* Normalize readme.txt to standard WordPress.org section format.

= 3.0.3 =
* 3.0.3-phase3h: Fix signup template display regression from phase3g by providing $nbtpl_templates to included template partials (wp-signup.php).
* 3.0.3-phase3g–3.0.3-phase3a: Phase 3 naming standards cleanup (prefixing, deprecated wrappers, deprecated hook mirroring, and class_alias back-compat).

= 3.0.2 =
* Phase 2 database access hardening (prepared SQL, sanitized query inputs, scoped PHPCS disables only where unavoidable for internal tables and bulk ops).

= 3.0.1 =
* Phase 1 security and UI output fixes complete; resolved WordPress.Security.* issues reported by Plugin Check.

= 3.0.0 =
* Modernization release baseline.

== Upgrade Notice ==
= 3.0.4 =
Maintenance release: enqueue parameter compliance + internal constant prefix normalization (back-compat preserved).
