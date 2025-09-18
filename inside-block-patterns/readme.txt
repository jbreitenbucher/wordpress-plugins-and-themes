=== Inside Block Patterns ===
Contributors: The College of Wooster (Jon Breitenbucher)
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: block editor, patterns, custom blocks, departments, college, university

Curated block patterns and custom blocks for university department and office sites. Accessible, editor-friendly, and theme-agnostic.

== Description ==
This plugin registers pragmatic block patterns and a small set of custom blocks tailored for academic departments and administrative offices. Editors can quickly assemble pages like section hubs, FAQ/Policy pages, people directories, and news/updates without wrestling the layout.

**What’s included**

- **Custom Blocks (Inside Blocks category)**
  - **Accordion (`ibp/accordion`)** and **Accordion Item (`ibp/accordion-item`)**
    - Keyboard and screen-reader friendly (button with `aria-expanded`, labelled panels, roving focus).
    - Works in the editor and front end; editor shows collapsible previews.
  - **Table of Contents (`ibp/toc`)**
    - Auto-generates a linked TOC from page headings.
    - Options: include H3–H6, set max depth, and “Collapse TOC by default.”
    - Editor has its own collapse toggle to save screen space.

- **Patterns (grouped under Inside- prefixed categories in the Patterns panel)**
  - **Department home**
    - Hero + quick links + news + contact.
    - About + services/audience tiles + recent news + contact/locations.
    - Audience gateways (Students/Faculty/Staff) + news.
  - **Section landing**
    - Left nav + main content + **TOC block** + optional FAQ.
  - **News & posts**
    - News article starter layout (validator-safe).
    - Updates grid (Query Loop, with featured images).
  - **People & directory**
    - Person card, row of 3, 3 columns, single row.
  - **Content utilities**
    - Mixed-media article (gallery + wrapped images + text).
    - FAQs using the **custom Accordion**.
    - Program requirements table scaffold.
    - Policy/doc download scaffolds.
    - Alerts/messaging banner.

**Editor/Theme compatibility**
- Patterns use an `.ibp-container` constrained wrapper and minimal CSS (`assets/css/ibp.css`) for intrinsic, responsive layouts.
- All remote placeholder images were replaced with **local plugin assets** to avoid hotlinking and cross-origin issues:
  - `assets/images/avatar-placeholder.png` for person cards.
  - `assets/images/hero-placeholder-1.jpg` and `hero-placeholder-2.jpg` for hero areas.
  - `assets/images/image-{1..6}.jpg` sprinkled into illustrative sections.
- Scripts and styles are registered with cache-busting (`filemtime`) and correct `wp.*` dependencies to avoid “Cannot read properties of undefined (reading 'blocks')”.

**Block & Pattern categories**
- **Blocks:** appear under **Inside Blocks** (slug `ibp-content`) in the block inserter.
- **Patterns:** appear under:
  - Inside Department & Office (`ibp-department`)
  - Inside Content Layouts (`ibp-content`)
  - Inside Messaging & Alerts (`ibp-messaging`)
  - Inside People & Directory (`ibp-people`)
  - Inside News & Posts (`ibp-news`)

== Installation ==
1. Upload the `inside-block-patterns` folder to `/wp-content/plugins/`.
2. Activate the plugin in **Plugins → Installed Plugins**.
3. In the editor:
   - Open the **Blocks** tab to find the **Inside Blocks** category (Accordion, Accordion Item, TOC).
   - Open the **Patterns** tab to find Inside-prefixed pattern categories.

== Frequently Asked Questions ==

= Why do my custom blocks show under “Uncategorized”? =
Your blocks’ `block.json` files declare `"category": "ibp-content"`. The plugin registers a matching blocks category (slug `ibp-content`) labelled **Inside Blocks**. If you still see “Uncategorized,” you’re likely loading a cached or conflicting category filter. Deactivate/reactivate the plugin and hard-refresh the editor.

= The editor said “Block contains unexpected or invalid content.” =
That happens when pattern markup doesn’t match your block’s `save()` output. For **static** custom blocks, patterns must include the rendered HTML between block comments. For **dynamic** blocks (with `render_callback`), patterns should include **only** block comments. We’ve updated our patterns accordingly (e.g., Accordion Items include their saved markup; TOC is dynamic and comment-only).

= Can I collapse the TOC in the editor? =
Yes. The TOC block has an editor-only toggle button that collapses the preview. The sidebar setting “Collapse TOC by default” controls the front-end default state.

= Why are images in patterns local instead of remote URLs? =
We ship placeholders in `assets/images/` and reference them so patterns work without external requests. This also fixes theme/editor validation issues and lets you swap assets in one place.

== Developer Notes ==
- We register block assets manually and pass handles to `register_block_type()` (so no `file:` entries are needed in `block.json`).
- **Accordion** (`ibp/accordion`, `ibp/accordion-item`): static blocks. Editor JS ensures the item has a stable `uid` when needed and mirrors front-end behavior without breaking validation.
- **TOC** (`ibp/toc`): **dynamic** block with `render_callback` for nested lists. Editor shows a robust preview with a separate collapse state. Attributes:  
  `title` (string), `includeH3..includeH6` (bool), `collapsed` (bool), `maxDepth` (int).
- Blocks register on `init` (priority 10); patterns register later (priority 20) so the parser recognizes custom blocks inside patterns.
- CSS is enqueued in both editor and front end and versioned via `filemtime` to avoid stale caches.

== Changelog ==

= 1.3.5 =
* **New:** Table of Contents block (`ibp/toc`) with include-levels, max depth, and “Collapse by default.” Accessible markup and editor collapse toggle.
* **New:** Section Landing pattern updated to use the **TOC block** in the left column and **FAQ** built from custom Accordion blocks.
* **Fix:** Blocks picker category now **Inside Blocks** (slug `ibp-content`). Removed slug mismatch that caused “Uncategorized.”
* **Fix:** Registered editor scripts with correct `wp.*` dependencies to eliminate `Cannot read properties of undefined (reading 'blocks')`.
* **Fix:** Replaced all remote placeholder images with **local assets** and updated patterns to use `assets/images/*`.
* **Polish:** Person card placeholder constrained (no full-width stretch by default).
* **Polish:** Mixed-media article’s left/right images use smaller placeholders to avoid full-column width in themes like Ollie.
* **Polish:** Department home (about + services + news + contact) width/containers corrected with `alignfull` + inner `alignwide ibp-container`.

= 1.3.1 =
* **Accordion:** Editor experience improved; items can be added/removed inline. Optional per-item `open` attribute supported.
* **News patterns:** Fixed “block cannot be rendered inside itself” by removing recursive Post Content usage and correcting block comment syntax.
* **Query Loop:** Updates grid pattern now includes featured images.

= 1.3.0 =
* **Assets:** Introduced `/assets/images/` placeholders and updated patterns accordingly.
* **CSS:** Consolidated utility classes in `assets/css/ibp.css` and enqueued in editor + front end with cache-busting.

= 1.2.1 =
* Single post template: removed Post Content to prevent recursion if inserted inside Post Content.
* Added “Post header + meta” helper (no Post Content) for Site Editor.

= 1.2.0 =
* Person card patterns added: single card, row of 3 (flex), 3 columns, and single row.

= 1.1.2 =
* Fixed nested block errors in News Article pattern and added a Site Editor-safe variant.

= 1.1.1 =
* Removed fragile blocks from FAQs/Section Landing and simplified Program Requirements table.

= 1.1.0 =
* Replaced fragile blocks (Cover, File, empty Image) with validator-safe markup; ensured buttons have href; general layout polish.

= 1.0.1 =
* Register patterns on `init` for plugin context (so they actually appear).

= 1.0.0 =
* Initial release.
