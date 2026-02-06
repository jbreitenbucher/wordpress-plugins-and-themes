<?php

namespace Wooster\LdapAuth\Provisioning;

use WP_User;
use Wooster\LdapAuth\Identity\UserIdentity;
use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class UserSynchronizer
{
    /**
     * @param WP_User $user
     * @param array<string,mixed> $dirUser
     */
    public function sync(WP_User $user, array $dirUser): void
    {
        $userId = (int) $user->ID;

        update_user_meta($userId, 'first_name', (string) ($dirUser['givenname'] ?? ''));
        update_user_meta($userId, 'last_name', (string) ($dirUser['sn'] ?? ''));

        // Mark LDAP-managed.
        UserIdentity::set_ldap_managed($userId, true);

        // Display name: nickname wins if present.
        $displayName = '';
        $nick = (string) ($dirUser['nickname'] ?? '');
        if ($nick !== '') {
            $displayName = $nick;
        } else {
            $mode = Options::get_string('ldapPublicDisplayName');
            $first = (string) ($dirUser['givenname'] ?? '');
            $last  = (string) ($dirUser['sn'] ?? '');
            $login = $user->user_login;

            switch ($mode) {
                case 'first':
                    $displayName = $first;
                    break;
                case 'firstlast':
                    $displayName = trim($first . ' ' . $last);
                    break;
                case 'lastfirst':
                    $displayName = trim($last . ' ' . $first);
                    break;
                case 'username':
                default:
                    $displayName = $login;
                    break;
            }
        }

        if ($displayName !== '' && $displayName !== $user->display_name) {
            wp_update_user([
                'ID' => $userId,
                'display_name' => $displayName,
            ]);
        }

        // Email: keep in sync if present and doesn't collide.
        $mail = (string) ($dirUser['mail'] ?? '');
        if ($mail !== '' && strtolower($mail) !== strtolower((string) $user->user_email)) {
            $existing = email_exists($mail);
            if (!$existing || (int) $existing === $userId) {
                wp_update_user([
                    'ID' => $userId,
                    'user_email' => $mail,
                ]);
            }
        }
    }
}
