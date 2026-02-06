<?php

namespace Wooster\LdapAuth\Admin;

use Wooster\LdapAuth\Ldap\Directory;
use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class NetworkSettingsPage
{
    private string $pluginFile;

    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    public function register(): void
    {
        if (!is_multisite()) {
            add_action('admin_menu', [$this, 'add_menu']);
        } else {
            add_action('network_admin_menu', [$this, 'add_menu']);
        }
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function add_menu(): void
    {
        $cap = is_multisite() ? 'manage_network_options' : 'manage_options';
        add_menu_page(
            __('LDAP Auth', 'ldap-auth'),
            __('LDAP Auth', 'ldap-auth'),
            $cap,
            'ldap-auth',
            [$this, 'render'],
            'dashicons-shield',
            80
        );
    }

    public function enqueue(string $hook): void
    {
        // Only on our screen.
        if (strpos($hook, 'ldap-auth') === false) {
            return;
        }
        wp_enqueue_style('ldap-auth-admin', plugins_url('includes/admin.css', $this->pluginFile), [], WOOSTER_LDAP_AUTH_VERSION);
    }

    public function render(): void
    {
        $cap = is_multisite() ? 'manage_network_options' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(__('You do not have permission to access this page.', 'ldap-auth'));
        }

        $notice = '';
        $testOutput = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('ldap_auth_settings');

            $action = isset($_POST['ldap_auth_action']) ? sanitize_key(wp_unslash($_POST['ldap_auth_action'])) : 'save';

            if ($action === 'test') {
                $lookup = isset($_POST['ldap_test_username']) ? sanitize_user(wp_unslash($_POST['ldap_test_username']), true) : '';
                $dir = new Directory();
                $u = $lookup ? $dir->lookup($lookup) : null;
                if (!$lookup) {
                    $testOutput = __('Enter a username to test.', 'ldap-auth');
                } elseif (!$u) {
                    $testOutput = __('No matching directory user found (or connection failed).', 'ldap-auth');
                } else {
                    // Redact DN a bit.
                    $dn = (string) ($u['dn'] ?? '');
                    if (strlen($dn) > 64) {
                        $dn = substr($dn, 0, 32) . '…' . substr($dn, -16);
                    }
                    $testOutput = sprintf(
                        "DN: %s\nEmail: %s\nGiven name: %s\nSurname: %s\nNickname: %s",
                        $dn,
                        (string) ($u['mail'] ?? ''),
                        (string) ($u['givenname'] ?? ''),
                        (string) ($u['sn'] ?? ''),
                        (string) ($u['nickname'] ?? '')
                    );
                }
            } else {
                $raw = wp_unslash($_POST);
                $sanitized = Options::sanitize_settings($raw);
                foreach ($sanitized as $k => $v) {
                    // Never store a blank password if the admin left the field empty.
                    if ($k === 'ldapServerPass' && $v === '') {
                        continue;
                    }
                    Options::update($k, $v);
                }
                $notice = __('Settings saved.', 'ldap-auth');
            }
        }

        $opts = Options::defaults();
        foreach (array_keys($opts) as $k) {
            $opts[$k] = Options::get($k, $opts[$k]);
        }

        // Never print secrets.
        $opts['ldapServerPass'] = '';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('LDAP Authentication', 'ldap-auth') . '</h1>';

        if ($notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('ldap_auth_settings');
        echo '<input type="hidden" name="ldap_auth_action" value="save" />';

        echo '<h2 class="nav-tab-wrapper">';
        echo '<a class="nav-tab nav-tab-active" href="#ldap-auth-connection">' . esc_html__('Connection', 'ldap-auth') . '</a>';
        echo '<a class="nav-tab" href="#ldap-auth-attrs">' . esc_html__('Attributes', 'ldap-auth') . '</a>';
        echo '<a class="nav-tab" href="#ldap-auth-groups">' . esc_html__('Group Access', 'ldap-auth') . '</a>';
        echo '<a class="nav-tab" href="#ldap-auth-behavior">' . esc_html__('Behavior', 'ldap-auth') . '</a>';
        echo '<a class="nav-tab" href="#ldap-auth-sso">' . esc_html__('SSO', 'ldap-auth') . '</a>';
        echo '</h2>';

        // CONNECTION
        echo '<div id="ldap-auth-connection" class="ldap-auth-panel">';
        $this->checkbox('ldapAuth', __('Enable LDAP authentication', 'ldap-auth'), $opts);
        $this->text('ldapServerAddr', __('LDAP server address', 'ldap-auth'), $opts, __('Hostname or IP. Do not include ldap:// unless you know you need it.', 'ldap-auth'));
        $this->text('ldapServerPort', __('LDAP server port', 'ldap-auth'), $opts);
        $this->text('ldapServerOU', __('Base DN (search base)', 'ldap-auth'), $opts, __('Example: ou=People,dc=example,dc=edu', 'ldap-auth'));
        $this->text('ldapServerCN', __('Bind user (CN or full DN)', 'ldap-auth'), $opts);
        $this->password('ldapServerPass', __('Bind password', 'ldap-auth'));
        $this->select('ldapEnableSSL', __('TLS/SSL mode', 'ldap-auth'), $opts, [
            0 => __('None (ldap://)', 'ldap-auth'),
            1 => __('LDAPS (ldaps://)', 'ldap-auth'),
            2 => __('StartTLS', 'ldap-auth'),
        ]);
        echo '</div>';

        // ATTRIBUTES
        echo '<div id="ldap-auth-attrs" class="ldap-auth-panel" style="display:none">';
        $this->select('ldapLinuxWindows', __('Directory type', 'ldap-auth'), $opts, [
            0 => __('Windows / AD (sAMAccountName)', 'ldap-auth'),
            1 => __('Linux / OpenLDAP (uid)', 'ldap-auth'),
        ]);
        $this->text('ldapAttributeWinSearch', __('Windows search attribute', 'ldap-auth'), $opts);
        $this->text('ldapAttributeNixSearch', __('Linux search attribute', 'ldap-auth'), $opts);
        $this->text('ldapAttributeMail', __('Email attribute', 'ldap-auth'), $opts);
        $this->text('ldapAttributeGivenname', __('Given name attribute', 'ldap-auth'), $opts);
        $this->text('ldapAttributeSn', __('Surname attribute', 'ldap-auth'), $opts);
        $this->text('ldapAttributeNickname', __('Nickname attribute', 'ldap-auth'), $opts);
        echo '</div>';

        // GROUPS
        echo '<div id="ldap-auth-groups" class="ldap-auth-panel" style="display:none">';
        $this->textarea('ldapGroupAllowLogin', __('Allow login (one group DN per line)', 'ldap-auth'), $opts);
        $this->textarea('ldapGroupDenyLogin', __('Deny login (one group DN per line)', 'ldap-auth'), $opts);
        echo '<p class="description">' . esc_html__('Group checks support nested groups and will stop after a reasonable recursion depth.', 'ldap-auth') . '</p>';
        echo '</div>';

        // BEHAVIOR
        echo '<div id="ldap-auth-behavior" class="ldap-auth-panel" style="display:none">';
        $this->checkbox('ldapCreateAcct', __('Auto-create WP users on first LDAP login', 'ldap-auth'), $opts);
        $this->checkbox('ldapCreateBlog', __('Auto-create a personal blog on first login (multisite)', 'ldap-auth'), $opts);
        $this->checkbox('ldapDisableSignup', __('Disable public signup', 'ldap-auth'), $opts);
        $this->select('ldapPublicDisplayName', __('Public display name', 'ldap-auth'), $opts, [
            '' => __('Default (username)', 'ldap-auth'),
            'username' => __('Username', 'ldap-auth'),
            'first' => __('First name', 'ldap-auth'),
            'firstlast' => __('First Last', 'ldap-auth'),
            'lastfirst' => __('Last First', 'ldap-auth'),
        ]);
        $this->checkbox('ldapDebug', __('Enable debug logging (requires WP_DEBUG_LOG)', 'ldap-auth'), $opts);
        echo '</div>';

        // SSO
        echo '<div id="ldap-auth-sso" class="ldap-auth-panel" style="display:none">';
        $this->checkbox('ldapSSOEnabled', __('Enable SSO (REMOTE_USER / LOGON_USER)', 'ldap-auth'), $opts);
        echo '<p class="description">' . esc_html__('SSO uses server-provided usernames and does not prompt for a password.', 'ldap-auth') . '</p>';
        echo '</div>';

        submit_button();
        echo '</form>';

        // Test form
        echo '<hr />';
        echo '<h2>' . esc_html__('Test LDAP lookup', 'ldap-auth') . '</h2>';
        echo '<form method="post" action="">';
        wp_nonce_field('ldap_auth_settings');
        echo '<input type="hidden" name="ldap_auth_action" value="test" />';
        echo '<p><label for="ldap_test_username">' . esc_html__('Username', 'ldap-auth') . '</label><br />';
        echo '<input type="text" id="ldap_test_username" name="ldap_test_username" class="regular-text" /></p>';
        submit_button(__('Test lookup', 'ldap-auth'), 'secondary');
        if ($testOutput) {
            echo '<pre class="ldap-auth-test">' . esc_html($testOutput) . '</pre>';
        }
        echo '</form>';

        // Lightweight tabs JS (no build tools, no React, no drama).
        echo '<script>document.addEventListener("DOMContentLoaded",function(){const tabs=document.querySelectorAll(".nav-tab-wrapper .nav-tab");const panels=document.querySelectorAll(".ldap-auth-panel");tabs.forEach(t=>t.addEventListener("click",e=>{e.preventDefault();tabs.forEach(x=>x.classList.remove("nav-tab-active"));t.classList.add("nav-tab-active");panels.forEach(p=>p.style.display="none");const id=t.getAttribute("href");const panel=document.querySelector(id);if(panel){panel.style.display="block";}}));});</script>';

        echo '</div>';
    }

    /**
     * @param array<string,mixed> $opts
     */
    private function checkbox(string $key, string $label, array $opts): void
    {
        $checked = !empty($opts[$key]) ? 'checked' : '';
        echo '<p><label><input type="checkbox" name="' . esc_attr($key) . '" value="1" ' . $checked . ' /> ' . esc_html($label) . '</label></p>';
    }

    /**
     * @param array<string,mixed> $opts
     */
    private function text(string $key, string $label, array $opts, string $desc = ''): void
    {
        echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br />';
        echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" class="regular-text" value="' . esc_attr((string) ($opts[$key] ?? '')) . '" />';
        if ($desc) {
            echo '<br /><span class="description">' . esc_html($desc) . '</span>';
        }
        echo '</p>';
    }

    private function password(string $key, string $label): void
    {
        echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br />';
        echo '<input type="password" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" class="regular-text" value="" autocomplete="new-password" />';
        echo '<br /><span class="description">' . esc_html__('Leave blank to keep the current saved password.', 'ldap-auth') . '</span>';
        echo '</p>';
    }

    /**
     * @param array<string,mixed> $opts
     * @param array<string|int,string> $choices
     */
    private function select(string $key, string $label, array $opts, array $choices): void
    {
        $current = (string) ($opts[$key] ?? '');
        echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br />';
        echo '<select id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">';
        foreach ($choices as $value => $text) {
            $sel = ((string) $value === $current) ? 'selected' : '';
            echo '<option value="' . esc_attr((string) $value) . '" ' . $sel . '>' . esc_html($text) . '</option>';
        }
        echo '</select></p>';
    }

    /**
     * @param array<string,mixed> $opts
     */
    private function textarea(string $key, string $label, array $opts): void
    {
        $value = $opts[$key] ?? [];
        $text = is_array($value) ? implode("\n", $value) : (string) $value;
        echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br />';
        echo '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" rows="6" cols="60" class="large-text code">' . esc_textarea($text) . '</textarea></p>';
    }
}
