<?php

namespace Wooster\LdapAuth\Provisioning;

use WP_Error;
use WP_User;
use Wooster\LdapAuth\Identity\UserIdentity;
use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class UserFactory
{
    /**
     * Ensure a WordPress user exists for this LDAP identity.
     *
     * @param array<string,mixed> $dirUser
     * @return WP_User|WP_Error
     */
    public function ensure_user(array $dirUser)
    {
        $username = (string) ($dirUser['username'] ?? '');
        $email = (string) ($dirUser['mail'] ?? '');

        if ($username === '') {
            return new WP_Error('ldap_username_empty', __('<strong>ERROR</strong>: Missing username from directory record.'));
        }

        $existing = get_user_by('login', $username);
        if ($existing instanceof WP_User) {
            return $existing;
        }

        if (!Options::get_bool('ldapCreateAcct')) {
            return new WP_Error('ldap_create_disabled', __('<strong>ERROR</strong>: Account creation is disabled.'));
        }

        if ($email === '') {
            return new WP_Error('ldapcreate_emailempty', sprintf(__('<strong>ERROR</strong>: <strong>%s</strong> does not have an email address associated with the directory record. All WordPress accounts must have a unique email address.'), esc_html($username)));
        }
        if (email_exists($email)) {
            return new WP_Error('ldapcreate_emailconflict', sprintf(__('<strong>ERROR</strong>: <strong>%s</strong> (%s) is already associated with another account. All accounts (including the admin account) must have a unique email address.'), esc_html($email), esc_html($username)));
        }

        $password = wp_generate_password(24, true, true);
        $userId = is_multisite()
            ? wpmu_create_user($username, $password, $email)
            : wp_create_user($username, $password, $email);

        if (!$userId || is_wp_error($userId)) {
            return is_wp_error($userId) ? $userId : new WP_Error('ldapcreate_failed', __('<strong>ERROR</strong>: Account creation from LDAP failed.'));
        }

        UserIdentity::set_ldap_managed((int) $userId, true);

        return new WP_User((int) $userId);
    }
}
