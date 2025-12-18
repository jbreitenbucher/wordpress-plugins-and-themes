# New Blog Templates (Modernized)

New Blog Templates is a WordPress Multisite plugin that allows network administrators to create site templates and apply them during the site signup process. This fork modernizes the original plugin with full support for:

- WordPress 6.8+ (Multisite mode)
- PHP 8.3 compatibility
- Updated admin UI and template selection workflow

This version fixes long-standing issues with deprecated functions, Network Admin menu placement, template category management, and AJAX-based filtering during signup.

---

## Features

- Create template sites from any site in the network
- Categorize templates for easier browsing
- Template selection UI integrated into `wp-signup.php`
- AJAX category filtering for large networks
- Fully functional in **Network Admin** (no orphan menus or broken links)
- Updated codebase: security, performance, and PHP 8+ compatibility improvements

---

## Requirements

| Component | Minimum |
|----------|---------|
| WordPress | 6.8 (Multisite) |
| PHP | 8.0 (tested through 8.3) |
| Database | MySQL / MariaDB supported by core WP |

This plugin **only applies** to networks where site creation is enabled.

---

## Installation

1. Enable Multisite in your WordPress installation.
2. Clone or download this repository into:
wp-content/plugins/blogtemplates/

yaml
Copy code
3. Network Activate the plugin from **Network Admin → Plugins**.
4. Verify Network Settings allow users to create new sites.

---

## Creating a Template

1. Pick an existing site on the network (excluding the main site).
2. Go to **Network Admin → Templates → Add New Template**.
3. Select the source site and assign categories.
4. Save — the template will now be available during site signup.

---

## Testing Signup Workflow

1. Visit your network’s signup page:
https://example.com/wp-signup.php

yaml
Copy code
2. Select a **Template Category** → Page dynamically updates with relevant templates
3. Choose a template and complete the signup
4. Newly launched site inherits content/settings from template source

---

## Development Notes

- AJAX endpoint improvements located in  
`blog_templates_theme_selection_toolbar.php` and `toolbar.js`
- Deprecation fixes and Security improvements are ongoing
- Future roadmap includes:
- Block-theme template support
- More granular cloning options (e.g., plugins, users, menu mapping)

Issue tracking and contributions welcome via GitHub.

---

## License

GPLv2 or later  
(Shared under WordPress plugin licensing standards)

---

## Credits

Originally created by WPMU DEV and community contributors.  
Modernized and maintained by The College of Wooster / EdTech community.

---

> Multisite should be a superpower, not a liability — this plugin helps keep it that way.