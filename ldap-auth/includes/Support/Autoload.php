<?php

namespace Wooster\LdapAuth\Support;

defined('ABSPATH') || exit;

/**
 * Very small PSR-4-ish autoloader for this plugin.
 *
 * We avoid Composer here on purpose: the plugin is often deployed as a single ZIP
 * into environments where Composer is not available.
 */
final class Autoload
{
    /** @var string */
    private const PREFIX = 'Wooster\\LdapAuth\\';

    /** @var string */
    private static string $baseDir;

    public static function register(string $pluginDir): void
    {
        self::$baseDir = rtrim($pluginDir, '/\\') . '/includes/';

        spl_autoload_register(static function (string $class): void {
            if (strpos($class, self::PREFIX) !== 0) {
                return;
            }

            $relative = substr($class, strlen(self::PREFIX));
            $relative = str_replace('\\', '/', $relative);
            $file = self::$baseDir . $relative . '.php';

            if (is_readable($file)) {
                require_once $file;
            }
        });
    }
}

// Register immediately when this file is required.
Autoload::register(dirname(__DIR__, 2));
