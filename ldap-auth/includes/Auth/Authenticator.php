<?php

namespace Wooster\LdapAuth\Auth;

use WP_Error;
use WP_User;
use Wooster\LdapAuth\Identity\UserIdentity;
use Wooster\LdapAuth\Ldap\Directory;
use Wooster\LdapAuth\Ldap\GroupChecker;
use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\Provisioning\BlogFactory;
use Wooster\LdapAuth\Provisioning\UserFactory;
use Wooster\LdapAuth\Provisioning\UserSynchronizer;
use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class Authenticator
{
    private Directory $directory;
    private GroupChecker $groups;
    private UserFactory $users;
    private UserSynchronizer $sync;
    private BlogFactory $blogs;

    public function __construct(?Directory $directory = null)
    {
        $this->directory = $directory ?: new Directory();
        $this->groups = new GroupChecker($this->directory);
        $this->users = new UserFactory();
        $this->sync = new UserSynchronizer();
        $this->blogs = new BlogFactory();
    }

    /**
     * WP authenticate filter.
     *
     * @param WP_User|WP_Error|null $user
     * @return WP_User|WP_Error|null
     */
    public function authenticate($user, string $username, string $password)
    {
        if ($user instanceof WP_User) {
            return $user;
        }
        if ($username === '' || $password === '') {
            return $user;
        }

        // If a local WP account exists and is NOT LDAP-managed, do not intercept.
        $existing = get_user_by('login', $username);
        if ($existing instanceof WP_User && !UserIdentity::is_ldap_managed((int) $existing->ID)) {
            return $user;
        }

        $dirUser = $this->directory->authenticate($username, $password);
        if (!$dirUser) {
            return $user;
        }

        $dn = (string) ($dirUser['dn'] ?? '');
        $groupResult = $this->groups->check_login($dn);
        if (!$groupResult['allowed']) {
            Logger::debug('LDAP auth denied by groups', ['username' => $username, 'reason' => $groupResult['reason']]);
            return new WP_Error('ldap_access_denied', __('<strong>ERROR</strong>: LDAP Access Denied.', 'ldap-auth'));
        }

        $wpUser = $this->users->ensure_user($dirUser);
        if (is_wp_error($wpUser)) {
            return $wpUser;
        }

        $this->sync->sync($wpUser, $dirUser);
        $this->blogs->maybe_create_blog($wpUser);

        UserGate::mark_ldap_authenticated();

        return $wpUser;
    }
}
