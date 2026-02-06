<?php

namespace Wooster\LdapAuth\Admin;

use Wooster\LdapAuth\Ldap\Directory;
use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\Provisioning\UserFactory;
use Wooster\LdapAuth\Provisioning\UserSynchronizer;

defined('ABSPATH') || exit;

final class AddUserPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu']);
        if (is_multisite()) {
            add_action('network_admin_menu', [$this, 'add_menu']);
        }
    }

    public function add_menu(): void
    {
        // Single-site: create_users + promote_users is the closest match.
        $cap = is_multisite() ? 'manage_network_users' : 'promote_users';
        add_users_page(
            __('LDAP Add User', 'ldap-auth'),
            __('LDAP Add User', 'ldap-auth'),
            $cap,
            'ldap-auth-add-user',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $cap = is_multisite() ? 'manage_network_users' : 'promote_users';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ldap-auth'));
        }

        $notice = '';
        $error = '';

        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if ('post' === $method) {
            check_admin_referer('ldap_auth_add_user');
            $username = isset($_POST['ldap_username']) ? sanitize_user(wp_unslash($_POST['ldap_username']), true) : '';
            $role = isset($_POST['role']) ? sanitize_key(wp_unslash($_POST['role'])) : 'subscriber';
            $allowLocal = !empty($_POST['allow_local_create']);
            $localEmail = isset($_POST['local_email']) ? sanitize_email(wp_unslash($_POST['local_email'])) : '';
            $blogId = is_multisite() ? get_current_blog_id() : 0;

            if ($username === '') {
                $error = __('Please enter a username.', 'ldap-auth');
            } else {
                $dir = new Directory();
                $dirUser = $dir->lookup($username);
                if (!$dirUser) {
                    if ($allowLocal && Options::get_bool('ldapCreateLocalUser')) {
                        if ($localEmail === '' || !is_email($localEmail)) {
                            $error = __('Local-create requires a valid email address.', 'ldap-auth');
                        } else {
                            $userId = wp_insert_user([
                                'user_login' => $username,
                                'user_email' => $localEmail,
                                'user_pass'  => wp_generate_password(24, true, true),
                                'role'       => is_multisite() ? '' : $role,
                            ]);
                            if (is_wp_error($userId)) {
                                $error = $userId->get_error_message();
                            } else {
                                if (is_multisite()) {
                                    $added = add_user_to_blog($blogId, (int) $userId, $role);
                                    if (is_wp_error($added)) {
                                        $error = $added->get_error_message();
                                    } else {
                                        $notice = __('Local user created and added to this site.', 'ldap-auth');
                                    }
                                } else {
                                    $notice = __('Local user created.', 'ldap-auth');
                                }
                            }
                        }
                    } else {
                        $error = __('No matching directory user found (or connection failed).', 'ldap-auth');
                    }
                } else {
                    $factory = new UserFactory();
                    $wpUser = $factory->ensure_user($dirUser);
                    if (is_wp_error($wpUser)) {
                        $error = $wpUser->get_error_message();
                    } else {
                        (new UserSynchronizer())->sync($wpUser, $dirUser);
                        if (is_multisite()) {
                            $added = add_user_to_blog($blogId, (int) $wpUser->ID, $role);
                            if (is_wp_error($added)) {
                                $error = $added->get_error_message();
                            } else {
                                $notice = __('User added to this site.', 'ldap-auth');
                            }
                        } else {
                            $notice = __('User exists (single-site).', 'ldap-auth');
                        }
                    }
                }
            }
        }

        $roles = wp_roles()->get_names();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('LDAP Add User', 'ldap-auth') . '</h1>';

        if ($notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
        }
        if ($error) {
            echo '<div class="notice notice-error"><p>' . wp_kses_post($error) . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('ldap_auth_add_user');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="ldap_username">' . esc_html__('Username', 'ldap-auth') . '</label></th>';
        echo '<td><input type="text" class="regular-text" name="ldap_username" id="ldap_username" /></td></tr>';

        if (is_multisite()) {
            echo '<tr><th scope="row"><label for="role">' . esc_html__('Role', 'ldap-auth') . '</label></th>';
            echo '<td><select name="role" id="role">';
            foreach ($roles as $k => $label) {
                echo '<option value="' . esc_attr($k) . '">' . esc_html($label) . '</option>';
            }
            echo '</select></td></tr>';
        }

        if (Options::get_bool('ldapCreateLocalUser')) {
            echo '<tr><th scope="row">' . esc_html__('Fallback', 'ldap-auth') . '</th><td>';
            echo '<label><input type="checkbox" name="allow_local_create" value="1" /> ' . esc_html__('If not found in LDAP, create a local user instead', 'ldap-auth') . '</label><br />';
            echo '<label for="local_email">' . esc_html__('Email', 'ldap-auth') . '</label><br />';
            echo '<input type="email" class="regular-text" name="local_email" id="local_email" />';
            echo '</td></tr>';
        }
        echo '</table>';
        submit_button(esc_html__('Lookup and add', 'ldap-auth'));
        echo '</form>';
        echo '</div>';
    }
}
