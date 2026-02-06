<?php

namespace Wooster\LdapAuth\UX;

use WP_Error;
use Wooster\LdapAuth\Identity\UserIdentity;
use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class UiToggles
{
    public function register(): void
    {
        add_filter('show_password_fields', [$this, 'show_password_fields'], 10, 2);
        add_filter('show_adduser_fields', [$this, 'show_adduser_fields']);
        add_filter('allow_password_reset', [$this, 'allow_password_reset'], 0, 2);
        add_action('admin_head-user-new.php', [$this, 'hide_add_user_box_css']);
    }

    public function show_password_fields($show, $user): bool
    {
        if (!Options::get_bool('ldapAuth')) {
            return (bool) $show;
        }
        $id = is_object($user) && isset($user->ID) ? (int) $user->ID : 0;
        if ($id && UserIdentity::is_ldap_managed($id)) {
            return false;
        }
        return (bool) $show;
    }

    public function show_adduser_fields($show): bool
    {
        // Preserve legacy behavior: hide built-in add user form when LDAP auth is on.
        if (Options::get_bool('ldapAuth')) {
            return false;
        }
        return (bool) $show;
    }

    public function allow_password_reset($allow, $userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return $allow;
        }
        if (UserIdentity::is_ldap_managed($userId)) {
            $msg = Options::get_string('ldapGetPasswordMessage', __('Password resets are managed by your institution.', 'ldap-auth'));
            return new WP_Error(
                'no_password_reset',
                __('<strong>ERROR</strong>: ', 'ldap-auth') . wp_kses_post($msg)
            );
        }
        return $allow;
    }

    public function hide_add_user_box_css(): void
    {
        if (!Options::get_bool('ldapAuth')) {
            return;
        }
        echo '<style>.add-new-user, #addnewuser {display:none !important;}</style>';
    }
}
