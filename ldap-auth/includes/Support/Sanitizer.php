<?php

namespace Wooster\LdapAuth\Support;

defined('ABSPATH') || exit;

final class Sanitizer
{
    public static function bool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int($value): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        return (int) $value;
    }

    public static function text($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        $value = (string) $value;
        $value = wp_unslash($value);
        return sanitize_text_field($value);
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    public static function dn_list($value): array
    {
        if (is_array($value)) {
            $lines = $value;
        } else {
            $value = (string) wp_unslash($value);
            $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }
        $out = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            // DNs can contain commas and equals; do not use sanitize_text_field (it strips some chars).
            $out[] = strtolower($line);
        }
        return array_values(array_unique($out));
    }
}
