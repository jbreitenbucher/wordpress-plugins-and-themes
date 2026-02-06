<?php

namespace Wooster\LdapAuth\Ldap;

use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class Client
{
    private \LDAP\Connection $conn;

    public function __construct(\LDAP\Connection $conn)
    {
        $this->conn = $conn;
    }

    public function bind(string $dn, string $password): bool
    {
        $ok = @ldap_bind($this->conn, $dn, $password)
            || @ldap_bind($this->conn, (string) $dn, (string) $password);
        if (!$ok) {
            Logger::debug('LDAP bind failed', ['dn' => $dn, 'errno' => @ldap_errno($this->conn), 'error' => @ldap_error($this->conn)]);
        }
        return (bool) $ok;
    }

    public function bind_anonymous(): bool
    {
        $ok = @ldap_bind($this->conn);
        if (!$ok) {
            Logger::debug('LDAP anonymous bind failed', ['errno' => @ldap_errno($this->conn), 'error' => @ldap_error($this->conn)]);
        }
        return (bool) $ok;
    }

    /**
     * @param string[] $attrs
     * @return array<string,mixed>|null
     */
    public function search_one(string $baseDn, string $filter, array $attrs): ?array
    {
        $result = @ldap_search($this->conn, $baseDn, $filter, $attrs);
        if (!$result) {
            Logger::debug('LDAP search failed', ['base' => $baseDn, 'filter' => $filter, 'errno' => @ldap_errno($this->conn)]);
            return null;
        }
        $entries = @ldap_get_entries($this->conn, $result);
        if (!is_array($entries) || empty($entries['count'])) {
            return null;
        }
        return $entries[0];
    }

    /**
     * @param string[] $attrs
     * @return array<int,array<string,mixed>>
     */
    public function search_all(string $baseDn, string $filter, array $attrs): array
    {
        $result = @ldap_search($this->conn, $baseDn, $filter, $attrs);
        if (!$result) {
            Logger::debug('LDAP search failed', ['base' => $baseDn, 'filter' => $filter, 'errno' => @ldap_errno($this->conn)]);
            return [];
        }
        $entries = @ldap_get_entries($this->conn, $result);
        if (!is_array($entries) || empty($entries['count'])) {
            return [];
        }
        $out = [];
        for ($i = 0; $i < (int) $entries['count']; $i++) {
            if (isset($entries[$i]) && is_array($entries[$i])) {
                $out[] = $entries[$i];
            }
        }
        return $out;
    }
}
