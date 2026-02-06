<?php

namespace Wooster\LdapAuth\Ldap;

use Wooster\LdapAuth\Options;
use Wooster\LdapAuth\Support\Logger;

defined('ABSPATH') || exit;

final class ConnectionFactory
{
    /**
     * @return \LDAP\Connection|resource|null
     */
    public function connect()
    {
        $server = trim(Options::get_string('ldapServerAddr'));
        $port   = Options::get_int('ldapServerPort', 0);
        $sslMode = Options::get_int('ldapEnableSSL', 0);

        if ($server === '') {
            Logger::debug('LDAP connect skipped: missing server', ['ssl_mode' => $sslMode, 'port' => $port]);
            return null;
        }

        $defaultPort = ($sslMode === 1) ? 636 : 389;
        if ($port <= 0) {
            $port = $defaultPort;
        }

        // If an admin pasted a full URI, respect it.
        if (preg_match('#^(ldap|ldaps)://#i', $server)) {
            $uri = $server;
        } else {
            if ($sslMode === 1) {
                $uri = 'ldaps://' . $server . (($port && $port !== 636) ? ':' . $port : '');
            } else {
                $uri = 'ldap://' . $server . ':' . $port;
            }
        }

        Logger::debug('LDAP connect attempt', ['uri' => $uri, 'ssl_mode' => $sslMode, 'port' => $port]);

        $conn = @ldap_connect($uri);
        if (!$conn) {
            Logger::debug('LDAP connect failed', ['uri' => $uri]);
            return null;
        }

        // Sensible defaults.
        @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        @ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 10);
        @ldap_set_option($conn, LDAP_OPT_TIMELIMIT, 10);

        // StartTLS: connect plain, then upgrade.
        if ($sslMode === 2) {
            $ok = @ldap_start_tls($conn);
            if (!$ok) {
                Logger::debug('LDAP StartTLS failed', ['errno' => @ldap_errno($conn), 'error' => @ldap_error($conn)]);
                return null;
            }
        }

        return $conn;
    }
}
