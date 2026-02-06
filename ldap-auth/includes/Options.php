<?php

namespace Wooster\LdapAuth;

use Wooster\LdapAuth\Support\Sanitizer;

defined('ABSPATH') || exit;

/**
 * Option access layer.
 *
 * Ground rules:
 * - We keep option keys exactly as they exist today.
 * - We provide typed getters and a single place to define defaults.
 */
final class Options
{
    /**
     * Option keys and defaults. Keep keys identical to the legacy plugin.
     *
     * @return array<string,mixed>
     */
    public static function defaults(): array
    {
        // Defaults used historically by the settings UI.
        $sysAdminEmail = (string) get_site_option('admin_email');
        $defaultSignupMessage = 'Public sign-up has been disabled.';
        $defaultGetPasswordMessage = "Your account is tied to an account in the central directory.  You cannot\n"
            . "retrieve your password via email.  Please contact the\n"
            . '<a href="mailto:' . esc_attr($sysAdminEmail) . '">system administrator</a> for information on how\n'
            . "to reset your password.";

        $defaultLocalEmailSubj = 'Blogging Account Created';
        $defaultLDAPEmailSubj  = 'Blogging Account Created';
        $defaultLocalEmailMessage = "Dear User,\n\nYou have just been permitted to access a new blog!\n\nUsername: USERNAME\nPassword: PASSWORD\nLogin: LOGINLINK\n\nWe hope you enjoy your new weblog.\n Thanks!\n\n--Wordpress";
        $defaultLDAPEmailMessage  = "Dear User,\n\nYou have just been permitted to access a new blog!\n\nUsername: USERNAME\nLogin: LOGINLINK\n\nWe hope you enjoy your new weblog.\n Thanks!\n\n--Wordpress";

        return [
            // Master toggles.
            'ldapAuth' => false,
            'ldapSSOEnabled' => false,

            // Provisioning / behavior.
            'ldapCreateAcct' => false,
            'ldapCreateBlog' => false,
            'ldapCreateLocalUser' => false,
            'ldapLinuxWindows' => false,
            'ldapDisableSignup' => false,
            'ldapAddUser' => 'enabled',
            'ldapBulkAdd' => false,
            'ldapPublicDisplayName' => '',
            'ldapLocalEmail' => false,
            'ldapLDAPEmail' => false,
            'ldapLocalEmailSubj' => $defaultLocalEmailSubj,
            'ldapLocalEmailMessage' => $defaultLocalEmailMessage,
            'ldapLDAPEmailSubj' => $defaultLDAPEmailSubj,
            'ldapLDAPEmailMessage' => $defaultLDAPEmailMessage,
            'ldapSignupMessage' => $defaultSignupMessage,
            'ldapGetPasswordMessage' => $defaultGetPasswordMessage,

            // Legacy maintenance toggles (kept for compatibility, even if unused).
            'ldapfixmetafor15' => false,
            'ldapfixdisplayname' => false,

            // Connection.
            'ldapServerAddr' => '',
            'ldapServerPort' => 389,
            'ldapServerOU' => '',
            'ldapServerCN' => '',
            'ldapServerPass' => '',
            // Historically 0/1; modernized to also accept 2 (StartTLS).
            'ldapEnableSSL' => 0,

            // Attribute mapping.
            'ldapAttributeMail' => 'mail',
            'ldapAttributeGivenname' => 'givenname',
            'ldapAttributeNickname' => '',
            'ldapAttributeSn' => 'sn',
            'ldapAttributePhone' => 'phone',
            'ldapAttributeHomedir' => 'homedirectory',
            'ldapAttributeMember' => 'member',
            'ldapAttributeMemberNix' => 'uniquemember',
            'ldapAttributeMacaddress' => 'zenwmmacaddress',
            // NOTE: legacy storage key is ldapAttributeDN (capital DN).
            'ldapAttributeDN' => 'dn',
            'ldapAttributeNixSearch' => 'uid',
            'ldapAttributeWinSearch' => 'samaccountname',
            'ldapAttributeGroupObjectclass' => 'group',
            'ldapAttributeGroupObjectclassNix' => 'groupofuniquenames',

            // Group access lists (stored as arrays of DNs).
            'ldapGroupAllowLogin' => [],
            'ldapGroupAllowLoginCreate' => [],
            'ldapGroupDenyLogin' => [],

            // Diagnostics.
            'ldapDebug' => false,
        ];
    }

    /**
     * Known legacy aliases (read canonical -> fallback to alias).
     *
     * @return array<string,string[]>
     */
    private static function aliases(): array
    {
        return [
            // The legacy code *reads* ldapAttributeDN but some installs may have saved ldapAttributeDn.
            'ldapAttributeDN' => ['ldapAttributeDn'],
        ];
    }

    public static function get(string $key, $default = null)
    {
        $defaults = self::defaults();
        if ($default === null && array_key_exists($key, $defaults)) {
            $default = $defaults[$key];
        }

        $value = get_site_option($key, $default);
        if ($value !== null && $value !== '' && $value !== false) {
            return $value;
        }

        foreach (self::aliases()[$key] ?? [] as $alias) {
            $aliasValue = get_site_option($alias, null);
            if ($aliasValue !== null && $aliasValue !== '' && $aliasValue !== false) {
                return $aliasValue;
            }
        }
        return $default;
    }

    public static function get_bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        // WordPress often stores options as strings.
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function get_int(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function get_string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);
        return is_string($value) ? $value : (string) $value;
    }

    /**
     * Update a known option key.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function update(string $key, $value): void
    {
        $defaults = self::defaults();
        if (!array_key_exists($key, $defaults)) {
            // Refuse unknown keys.
            return;
        }

        update_site_option($key, $value);

        // Mirror to aliases when present for maximum compatibility.
        foreach (self::aliases()[$key] ?? [] as $alias) {
            update_site_option($alias, $value);
        }
    }

    /**
     * Sanitize incoming settings payload.
     *
     * @param array<string,mixed> $raw
     * @return array<string,mixed>
     */
    public static function sanitize_settings(array $raw): array
    {
        $out = [];
        $defaults = self::defaults();

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $value = $raw[$key];

            if (is_bool($default)) {
                $out[$key] = Sanitizer::bool($value);
            } elseif (is_int($default)) {
                $out[$key] = Sanitizer::int($value);
            } elseif (is_array($default)) {
                // Group lists: accept textarea with newline-delimited DNs or an array.
                $out[$key] = Sanitizer::dn_list($value);
            } else {
                // Strings.
                $out[$key] = Sanitizer::text($value);
            }
        }

        // Normalize special cases.
        if (isset($out['ldapEnableSSL'])) {
            // Allow 0/1/2.
            $out['ldapEnableSSL'] = max(0, min(2, (int) $out['ldapEnableSSL']));
        }
        if (isset($out['ldapServerPort'])) {
            $out['ldapServerPort'] = max(0, (int) $out['ldapServerPort']);
        }
        if (isset($out['ldapAddUser'])) {
            $out['ldapAddUser'] = ($out['ldapAddUser'] === 'disabled') ? 'disabled' : 'enabled';
        }

        return $out;
    }
}
