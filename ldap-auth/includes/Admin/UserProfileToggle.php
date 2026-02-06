<?php

namespace Wooster\LdapAuth\Admin;

use Wooster\LdapAuth\Identity\UserIdentity;

defined('ABSPATH') || exit;

final class UserProfileToggle
{
    public function register(): void
    {
        add_action('show_user_profile', [$this, 'render']);
        add_action('edit_user_profile', [$this, 'render']);
        add_action('personal_options_update', [$this, 'save']);
        add_action('edit_user_profile_update', [$this, 'save']);
    }

    public function render($user): void
    {
        if (!($user instanceof \WP_User)) {
            return;
        }
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $managed = UserIdentity::is_ldap_managed((int) $user->ID);
        wp_nonce_field('ldap_auth_profile_toggle', 'ldap_auth_profile_nonce');

        echo '<h2>' . esc_html__('LDAP Authentication', 'ldap-auth') . '</h2>';
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th><label for="ldap_login_toggle">' . esc_html__('LDAP-managed account', 'ldap-auth') . '</label></th><td>';
        echo '<label><input type="checkbox" id="ldap_login_toggle" name="ldap_login_toggle" value="1" ' . checked($managed, true, false) . ' /> ' . esc_html__('This account is managed by LDAP (local password login is blocked).', 'ldap-auth') . '</label>';
        echo '</td></tr>';
        echo '</table>';
    }

    public function save(int $userId): void
    {
        if (!current_user_can('edit_user', $userId)) {
            return;
        }
        if (empty($_POST['ldap_auth_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ldap_auth_profile_nonce'])), 'ldap_auth_profile_toggle')) {
            return;
        }
        $managed = !empty($_POST['ldap_login_toggle']);
        UserIdentity::set_ldap_managed($userId, $managed);
    }
}
