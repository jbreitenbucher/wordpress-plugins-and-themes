<?php

namespace Wooster\LdapAuth\Identity;

defined('ABSPATH') || exit;

final class UserIdentity
{
    public const META_KEY = 'ldap_login';

    public static function is_ldap_managed(int $userId): bool
    {
        $v = get_user_meta($userId, self::META_KEY, true);
        return is_string($v) && strtolower($v) === 'true';
    }

    public static function set_ldap_managed(int $userId, bool $managed): void
    {
        if ($managed) {
            update_user_meta($userId, self::META_KEY, 'true');
        } else {
            delete_user_meta($userId, self::META_KEY);
        }
    }
}
