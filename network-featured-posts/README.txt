=== Network Featured Posts Block ===
Contributors: wooster
Tags: block, gutenberg, pdf, sharepoint
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Feature posts from any allowed site filtered by category and date.

== Description ==

A network-activated plugin that adds a dynamic block to display recent posts across a WordPress Multisite network.

Highlights
- Network-level index table for fast querying (works for very large networks).
- Incremental updates when posts are saved/published/unpublished.
- Network Admin settings: allowlist sites + cache TTL + backfill tool.
- REST-powered site picker in the block editor (search + add by ID).

== Installation ==

1) Upload and network-activate the plugin.
2) Network Admin → Settings → Network Featured Posts → Start Backfill (for existing content).
3) Add the “Network Featured Posts” block to a page.

== Notes ==

- MVP indexes post type "post" only. Expanding to CPTs is straightforward: indexer.php + UI option + backfill query.
