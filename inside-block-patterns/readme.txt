=== College Network Block Patterns ===
Contributors: your-agency
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: block editor, patterns, departments, college, university

A set of pragmatic block patterns tailored for university department and office sites.

== Description ==
This plugin registers curated block patterns commonly needed by department/office sites: a department home hero with quick links, section landing with left nav and table of contents, FAQs, document downloads, a news article header layout, staff directory cards, alert banner, updates grid, policy page scaffold, and a program requirements table.

Patterns are intrinsic and responsive by default using a tiny opt-in CSS file enqueued by the plugin.

== Installation ==
1. Upload the `college-network-block-patterns` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the editor, open the Patterns tab and look for categories beginning with “Department & Office,” “Content Layouts,” “Messaging & Alerts,” “People & Directory,” and “News & Posts.”

== Changelog ==
= 1.2.1 =
* Single post template: removed Post Content to prevent recursion if inserted inside Post Content.
* Added 'Post header + meta' helper (no Post Content) for Site Editor; place it above your Post Content block.

= 1.2.0 =
* Add Person card patterns: single card, row of 3 (flex), 3 columns, and row of 1.

= 1.1.2 =
* Simplify News Article pattern (remove post-content inside same layout) to fix nested block error.

= 1.2.1 =
* Single post template: removed Post Content to prevent recursion if inserted inside Post Content.
* Added 'Post header + meta' helper (no Post Content) for Site Editor; place it above your Post Content block.

= 1.2.0 =
* Add Person card patterns: single card, row of 3 (flex), 3 columns, and row of 1.

= 1.1.2 =
* Fix 'block cannot be rendered inside itself' by removing post-* blocks from the content pattern.
* Add a Site Editor template variant that uses Post Title/Date/Content safely.

= 1.1.1 =
* Remove fragile blocks (Details, Navigation) from FAQs and Section Landing.
* Simplify Table markup (no footer/fixed layout) for Program Requirements.

= 1.1.0 =
* Replace fragile blocks (Cover, File, empty Image) with validator-safe markup.
* Ensure buttons have href.
* Minor layout polish for better theme compatibility.

= 1.0.1 =
* Register patterns on init for plugins (fix: patterns not showing).

= 1.0.0 =
* Initial release.

