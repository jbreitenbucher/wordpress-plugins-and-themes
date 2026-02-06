<?php

namespace Wooster\LdapAuth\UX;

use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class SignupBlocker
{
    public function register(): void
    {
        add_action('signup_header', [$this, 'maybe_block_signup']);
        add_action('login_head', [$this, 'maybe_block_login_signup_links']);
    }

    public function maybe_block_signup(): void
    {
        if (!Options::get_bool('ldapDisableSignup')) {
            return;
        }
        wp_die(
            esc_html__("Signup is disabled for this site.", 'ldap-auth'),
            esc_html__("Signup disabled", 'ldap-auth'),
            ['response' => 403]
        );
    }

    public function maybe_block_login_signup_links(): void
    {
        if (!Options::get_bool('ldapDisableSignup')) {
            return;
        }
        // Hide signup/register links on the login screen.
        echo '<style>.login #nav a[href*="action=register"], .login #nav a[href*="wp-signup.php"]{display:none!important}</style>';
    }
}
