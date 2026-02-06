<?php

namespace Wooster\LdapAuth\Support;

use Wooster\LdapAuth\Options;

defined('ABSPATH') || exit;

final class Logger
{
    private const PREFIX = '[ldap-auth]';

    /**
     * @param string $message
     * @param array<string,mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }
        if (!Options::get_bool('ldapDebug')) {
            return;
        }
        $safe = self::redact($context);
        error_log(self::PREFIX . ' ' . $message . (empty($safe) ? '' : ' ' . wp_json_encode($safe)));
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private static function redact(array $context): array
    {
        $redactedKeys = ['password', 'pass', 'bind_password', 'ldapServerPass'];
        foreach ($redactedKeys as $k) {
            if (array_key_exists($k, $context)) {
                $context[$k] = '***';
            }
        }
        // Avoid logging very long DNs in full.
        foreach (['dn', 'user_dn', 'bind_dn'] as $k) {
            if (isset($context[$k]) && is_string($context[$k]) && strlen($context[$k]) > 64) {
                $context[$k] = substr($context[$k], 0, 32) . '…' . substr($context[$k], -16);
            }
        }
        return $context;
    }
}
