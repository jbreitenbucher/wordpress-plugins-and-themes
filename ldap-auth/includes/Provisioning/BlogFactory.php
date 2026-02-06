<?php

namespace Wooster\LdapAuth\Provisioning;

use WP_Error;
use WP_User;
use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class BlogFactory
{
    /**
     * Create a personal blog for the user on first login, if enabled.
     *
     * This preserves the legacy behavior as closely as practical.
     */
    public function maybe_create_blog(WP_User $user): void
    {
        if (!is_multisite() || !Options::get_bool('ldapCreateBlog')) {
            return;
        }

        $network = get_network();
        if (!$network) {
            return;
        }

        $username = strtolower($user->user_login);
        $basePath = trailingslashit($network->path);

        if (is_subdomain_install()) {
            $domain = $username . '.' . $network->domain;
            $path = $basePath;
        } else {
            $domain = $network->domain;
            $path = $basePath . $username . '/';
        }

        // If a site already exists for that domain/path, do nothing.
        $existing = get_site_by_path($domain, $path);
        if ($existing) {
            return;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP hook.
        $meta = apply_filters('signup_create_blog_meta', ['lang_id' => 'en', 'public' => 0]);
        $title = $username . "'s blog";
        $blogId = wpmu_create_blog($domain, $path, $title, (int) $user->ID, $meta, (int) $network->id);
        if (is_wp_error($blogId)) {
            return;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP hook.
        do_action('wpmu_activate_blog', $blogId, $user->ID, '', $title, $meta);
    }
}
