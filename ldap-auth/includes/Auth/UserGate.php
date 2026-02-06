<?php

namespace Wooster\LdapAuth\Auth;

use WP_Error;
use WP_User;
use Wooster\LdapAuth\Identity\UserIdentity;
use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class UserGate
{
    private static bool $ldapAuthedThisRequest = false;

    public static function mark_ldap_authenticated(): void
    {
        self::$ldapAuthedThisRequest = true;
    }

    /**
     * @param WP_User|WP_Error $user
     * @return WP_User|WP_Error
     */
    public function block_local_login($user)
    {
        if (!Options::get_bool('ldapAuth')) {
            return $user;
        }
        if (!($user instanceof WP_User)) {
            return $user;
        }
        if (self::$ldapAuthedThisRequest) {
            return $user;
        }
        if (UserIdentity::is_ldap_managed((int) $user->ID)) {
            return new WP_Error('ldap_local_login_blocked', __('<strong>ERROR</strong>: Local password login is disabled for LDAP-managed accounts.'));
        }
        return $user;
    }
}
