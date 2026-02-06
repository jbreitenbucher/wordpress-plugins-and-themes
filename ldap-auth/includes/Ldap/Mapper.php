<?php

namespace Wooster\LdapAuth\Ldap;

use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class Mapper
{
    /**
     * Extract a DN from an LDAP entry.
     *
     * LDAP results may provide:
     *  - $entry['dn'] as a string
     *  - a configured DN attribute (often array-valued) like distinguishedName
     *
     * @param array<string,mixed> $entry
     */
    public static function extract_dn(array $entry, ?string $dnAttr = null): string
    {
        if (isset($entry['dn']) && is_string($entry['dn'])) {
            return $entry['dn'];
        }

        $dnAttr = $dnAttr ? strtolower($dnAttr) : strtolower(Options::get_string('ldapAttributeDN', 'dn'));
        if ($dnAttr !== '' && isset($entry[$dnAttr])) {
            $val = $entry[$dnAttr];
            if (is_array($val) && isset($val[0]) && is_string($val[0])) {
                return $val[0];
            }
            if (is_string($val)) {
                return $val;
            }
        }

        return '';
    }

    /**
     * Attributes to request for user lookup.
     *
     * @return string[]
     */
    public static function requested_attributes(): array
    {
        return [
            Options::get_string('ldapAttributeMail', 'mail'),
            Options::get_string('ldapAttributeGivenname', 'givenname'),
            Options::get_string('ldapAttributeSn', 'sn'),
            Options::get_string('ldapAttributePhone', 'phone'),
            Options::get_string('ldapAttributeNickname', ''),
        ];
    }

    /**
     * Normalize an LDAP entry to a stable directory-user structure.
     *
     * @param array<string,mixed> $entry
     * @return array<string,mixed>
     */
    public static function normalize_entry(array $entry, string $username): array
    {
        $mailAttr = Options::get_string('ldapAttributeMail', 'mail');
        $givenAttr = Options::get_string('ldapAttributeGivenname', 'givenname');
        $snAttr = Options::get_string('ldapAttributeSn', 'sn');
        $phoneAttr = Options::get_string('ldapAttributePhone', 'phone');
        $nickAttr = Options::get_string('ldapAttributeNickname', '');

        $dn = self::extract_dn($entry);

        return [
            'dn' => $dn,
            'username' => $username,
            // common LDAP keys are lowercased by ldap_get_entries
            'mail' => self::first($entry, $mailAttr),
            'givenname' => self::first($entry, $givenAttr),
            'sn' => self::first($entry, $snAttr),
            'phone' => self::first($entry, $phoneAttr),
            'nickname' => $nickAttr ? self::first($entry, $nickAttr) : '',
        ];
    }

    private static function first(array $entry, string $attr): string
    {
        $attr = strtolower($attr);
        if ($attr === '' || !isset($entry[$attr])) {
            return '';
        }
        $val = $entry[$attr];
        if (is_array($val) && isset($val[0])) {
            return (string) $val[0];
        }
        return is_string($val) ? $val : '';
    }
} 
