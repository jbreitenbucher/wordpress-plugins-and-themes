<?php

namespace Wooster\LdapAuth\Admin;

use Wooster\LdapAuth\Ldap\Directory;
use Wooster\LdapAuth\Provisioning\UserFactory;
use Wooster\LdapAuth\Provisioning\UserSynchronizer;

defined('ABSPATH') || exit;

final class BulkAddUserPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu(): void
    {
        if (!$this->can_access()) {
            return;
        }
        add_users_page(
            __('LDAP Bulk Add Users', 'ldap-auth'),
            __('LDAP Bulk Add', 'ldap-auth'),
            $this->required_cap(),
            'ldap-auth-bulk-add-users',
            [$this, 'render']
        );
    }

    private function required_cap(): string
    {
        return is_multisite() ? 'manage_network_users' : 'promote_users';
    }

    private function can_access(): bool
    {
        return current_user_can($this->required_cap());
    }

    public function render(): void
    {
        if (!$this->can_access()) {
            wp_die(__('You do not have permission to access this page.', 'ldap-auth'));
        }

        $notice = '';
        $results = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('ldap_auth_bulk_add_users');

            $role = isset($_POST['role']) ? sanitize_key(wp_unslash($_POST['role'])) : 'subscriber';
            $raw = isset($_POST['usernames']) ? (string) wp_unslash($_POST['usernames']) : '';
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            $usernames = [];
            foreach ($lines as $l) {
                $l = trim($l);
                if ($l !== '') {
                    $usernames[] = sanitize_user($l, true);
                }
            }
            $usernames = array_values(array_unique(array_filter($usernames)));

            $dir = new Directory();
            $factory = new UserFactory();
            $sync = new UserSynchronizer();
            $blogId = get_current_blog_id();

            foreach ($usernames as $u) {
                $r = ['username' => $u, 'status' => 'skipped', 'message' => ''];
                $du = $dir->lookup($u);
                if (!$du) {
                    $r['status'] = 'not_found';
                    $r['message'] = __('Not found in directory (or lookup failed).', 'ldap-auth');
                    $results[] = $r;
                    continue;
                }
                $wpUser = $factory->ensure_user($du);
                if (is_wp_error($wpUser)) {
                    $r['status'] = 'error';
                    $r['message'] = $wpUser->get_error_message();
                    $results[] = $r;
                    continue;
                }
                $sync->sync($wpUser, $du);
                $added = add_user_to_blog($blogId, (int) $wpUser->ID, $role);
                if (is_wp_error($added)) {
                    $r['status'] = 'error';
                    $r['message'] = $added->get_error_message();
                } else {
                    $r['status'] = 'added';
                    $r['message'] = __('Added to site.', 'ldap-auth');
                }
                $results[] = $r;
            }
            $notice = __('Bulk add processed.', 'ldap-auth');
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('LDAP Bulk Add Users', 'ldap-auth') . '</h1>';
        if ($notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
        }
        echo '<form method="post">';
        wp_nonce_field('ldap_auth_bulk_add_users');

        echo '<p><label for="usernames">' . esc_html__('Usernames (one per line)', 'ldap-auth') . '</label><br />';
        echo '<textarea id="usernames" name="usernames" rows="10" cols="60" class="large-text code"></textarea></p>';

        echo '<p><label for="role">' . esc_html__('Role', 'ldap-auth') . '</label><br />';
        wp_dropdown_roles('subscriber');
        echo '</p>';

        submit_button(__('Add Users', 'ldap-auth'));
        echo '</form>';

        if (!empty($results)) {
            echo '<h2>' . esc_html__('Results', 'ldap-auth') . '</h2>';
            echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Username', 'ldap-auth') . '</th><th>' . esc_html__('Status', 'ldap-auth') . '</th><th>' . esc_html__('Message', 'ldap-auth') . '</th></tr></thead><tbody>';
            foreach ($results as $r) {
                echo '<tr><td>' . esc_html($r['username']) . '</td><td>' . esc_html($r['status']) . '</td><td>' . esc_html($r['message']) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }
}
