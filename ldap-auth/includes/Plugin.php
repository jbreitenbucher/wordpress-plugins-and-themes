<?php

namespace Wooster\LdapAuth;

use Wooster\LdapAuth\Admin\AddUserPage;
use Wooster\LdapAuth\Admin\BulkAddUserPage;
use Wooster\LdapAuth\Admin\NetworkSettingsPage;
use Wooster\LdapAuth\Admin\UserProfileToggle;
use Wooster\LdapAuth\Auth\Authenticator;
use Wooster\LdapAuth\Auth\SsoAuthenticator;
use Wooster\LdapAuth\Auth\UserGate;
use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\UX\SignupBlocker;
use Wooster\LdapAuth\UX\UiToggles;

defined('ABSPATH') || exit;

final class Plugin
{
    private static ?self $instance = null;

    private string $pluginFile;

    private function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    public static function instance(string $pluginFile): self
    {
        if (!self::$instance) {
            self::$instance = new self($pluginFile);
        }
        return self::$instance;
    }

    public function plugin_file(): string
    {
        return $this->pluginFile;
    }

    public function init(): void
    {
        // Admin UI always loads (settings may be needed even when ldapAuth disabled).
        (new NetworkSettingsPage($this->pluginFile))->register();
        (new AddUserPage($this->pluginFile))->register();
        (new BulkAddUserPage($this->pluginFile))->register();
        (new UserProfileToggle())->register();

        // UX toggles only when LDAP auth is enabled.
        if (Options::get_bool('ldapAuth')) {
            (new UiToggles())->register();

            if (Options::get_bool('ldapDisableSignup')) {
                (new SignupBlocker())->register();
            }

            // Auth pipeline.
            $authenticator = new Authenticator();
            add_filter('authenticate', [$authenticator, 'authenticate'], 25, 3);
            add_filter('wp_authenticate_user', [new UserGate(), 'block_local_login'], 10, 1);

            if (Options::get_bool('ldapSSOEnabled')) {
                $sso = new SsoAuthenticator();
                add_filter('authenticate', [$sso, 'authenticate'], 40, 3);
                add_filter('login_url', [$sso, 'filter_login_url'], 10, 1);
            }
        }
    }
}
