=== Wooster SharePoint PDF Block ===
Contributors: wooster
Tags: block, gutenberg, pdf, sharepoint
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed SharePoint PDFs using “Anyone” share links via a consistent, self-hosted PDF.js viewer (no Media Library uploads).

== Description ==

This plugin provides a Gutenberg block that embeds a SharePoint PDF using an “Anyone” share link (tenant: livewooster.sharepoint.com) without requiring editors to use HTML blocks/iframes and without uploading PDFs to the Media Library.

It renders PDFs using a minimal, self-hosted PDF.js canvas viewer for consistent behavior across browsers:

* Continuous scroll (pages stacked vertically)
* Fit-to-width rendering
* No text layer and no annotation tools
* Includes Download and Print buttons

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install the ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the Plugins menu.
3. In the block editor, insert “Wooster SharePoint PDF” (category: Wooster Blocks).

== Frequently Asked Questions ==

= Does this upload PDFs to the Media Library? =

No. The PDF is proxied from SharePoint via a WordPress REST endpoint.

= Does this iframe the raw PDF? =

No. It iframes a self-hosted PDF.js viewer for consistent behavior.

== Changelog ==

= 1.0.0 =
* Initial release.
