<?php

namespace Wooster\LdapAuth\Auth;

use WP_Error;
use WP_User;
use Wooster\LdapAuth\Ldap\Directory;
use Wooster\LdapAuth\Ldap\GroupChecker;
use Wooster\LdapAuth\Provisioning\BlogFactory;
use Wooster\LdapAuth\Provisioning\UserFactory;
use Wooster\LdapAuth\Provisioning\UserSynchronizer;
use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class SsoAuthenticator
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
     * @param WP_User|WP_Error|null $user
     * @return WP_User|WP_Error|null
     */
    public function authenticate($user, string $username, string $password)
    {
        if ($user instanceof WP_User) {
            return $user;
        }

        // If a password is being submitted, let the password authenticator handle it.
        if ($username !== '' && $password !== '') {
            return $user;
        }

        $ssoUser = $this->read_server_username();
        if ($ssoUser === '') {
            return $user;
        }

        $dirUser = $this->directory->lookup($ssoUser);
        if (!$dirUser) {
            return $user;
        }

        $dn = (string) ($dirUser['dn'] ?? '');
        $groupResult = $this->groups->check_login($dn);
        if (!$groupResult['allowed']) {
            Logger::debug('SSO denied by groups', ['username' => $ssoUser, 'reason' => $groupResult['reason']]);
            return new WP_Error('ldap_access_denied', __('<strong>ERROR</strong>: LDAP Access Denied.'));
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

    public function filter_login_url(string $loginUrl): string
    {
        // Preserve legacy behavior: remove reauth only when SSO is enabled.
        return remove_query_arg('reauth', $loginUrl);
    }

    private function read_server_username(): string
    {
        $candidates = [
            'LOGON_USER',
            'REMOTE_USER',
            'AUTH_USER',
        ];
        foreach ($candidates as $k) {
            if (!empty($_SERVER[$k]) && is_string($_SERVER[$k])) {
                $u = trim((string) $_SERVER[$k]);
                if ($u !== '') {
                    // Strip DOMAIN\user.
                    if (strpos($u, '\\') !== false) {
                        $u = substr($u, strrpos($u, '\\') + 1);
                    }
                    return sanitize_user($u, true);
                }
            }
        }
        return '';
    }
}
