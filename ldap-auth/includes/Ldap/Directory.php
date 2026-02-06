<?php

namespace Wooster\LdapAuth\Ldap;

use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class Directory
{
    private ConnectionFactory $factory;

    public function __construct(?ConnectionFactory $factory = null)
    {
        $this->factory = $factory ?: new ConnectionFactory();
    }

    /**
     * Authenticate a username/password by searching for the user DN then binding as the user.
     *
     * @return array<string,mixed>|null Directory user or null on failure
     */
    public function authenticate(string $username, string $password): ?array
    {
        $username = $this->normalize_username($username);
        if ($username === '' || $password === '') {
            return null;
        }

        $conn = $this->factory->connect();
        if (!$conn) {
            return null;
        }
        $client = new Client($conn);

        // Bind using service account (CN/pass) if provided; else anonymous.
        if (!$this->bind_service($client)) {
            // Still allow direct user bind flows in some directories.
            Logger::debug('LDAP service bind failed; falling back to anonymous bind');
            $client->bind_anonymous();
        }

        $user = $this->lookup_user($client, $username);
        if (!$user) {
            return null;
        }

        $userDn = (string) ($user['dn'] ?? '');
        if ($userDn === '') {
            return null;
        }

        // Verify credentials.
        $verifyConn = $this->factory->connect();
        if (!$verifyConn) {
            return null;
        }
        $verifyClient = new Client($verifyConn);
        if (!$verifyClient->bind($userDn, $password)) {
            return null;
        }

        return $user;
    }

    /**
     * Lookup a directory user without binding as that user (used by SSO and admin lookup).
     *
     * @return array<string,mixed>|null
     */
    public function lookup(string $username): ?array
    {
        $username = $this->normalize_username($username);
        if ($username === '') {
            return null;
        }
        $conn = $this->factory->connect();
        if (!$conn) {
            return null;
        }
        $client = new Client($conn);
        if (!$this->bind_service($client)) {
            $client->bind_anonymous();
        }
        return $this->lookup_user($client, $username);
    }

    private function normalize_username(string $username): string
    {
        $username = trim($username);
        // Strip DOMAIN\\user (Windows auth) if present.
        if (strpos($username, '\\') !== false) {
            $username = substr($username, strrpos($username, '\\') + 1);
        }
        return sanitize_user($username, true);
    }

    public function bind_service(Client $client): bool
    {
        $cn   = trim(Options::get_string('ldapServerCN'));
        $pass = Options::get_string('ldapServerPass');
        $base = trim(Options::get_string('ldapServerOU'));

        if ($cn === '' || $pass === '') {
            return false;
        }

        // Legacy meaning: ldapServerCN stored a DN-ish string for bind user.
        // If it doesn't look like a DN, build `cn=<cn>,<baseDN>`.
        $bindDn = (stripos($cn, '=') !== false) ? $cn : ('cn=' . $cn . ($base ? ',' . $base : ''));
        Logger::debug('LDAP service bind attempt', ['bind_dn' => $bindDn]);
        return $client->bind($bindDn, $pass);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function lookup_user(Client $client, string $username): ?array
    {
        $baseDn = trim(Options::get_string('ldapServerOU'));
        if ($baseDn === '') {
            // Historically required.
            Logger::debug('LDAP search skipped: missing search base (ldapServerOU)');
            return null;
        }

        // Search attribute depends on Linux vs Windows.
        $uidAttr = Options::get_bool('ldapLinuxWindows')
            ? Options::get_string('ldapAttributeNixSearch', 'uid')
            : Options::get_string('ldapAttributeWinSearch', 'samaccountname');

        $attrs = Mapper::requested_attributes();
        // Always request DN.
        $attrs[] = Options::get_string('ldapAttributeDN', 'dn');
        $attrs = array_values(array_unique(array_filter($attrs)));

        $filter = '(' . $uidAttr . '=' . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')';
        $entry = $client->search_one($baseDn, $filter, $attrs);
        if (!$entry) {
            return null;
        }
        $user = Mapper::normalize_entry($entry, $username);
        Logger::debug('LDAP user found', ['username' => $username, 'dn' => $user['dn'] ?? null]);
        return $user;
    }
}
