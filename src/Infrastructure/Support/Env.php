<?php

declare(strict_types=1);

namespace App\Infrastructure\Support;

use RuntimeException;

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            if ($line === false) {
                continue;
            }

            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }

            [$key, $value] = array_pad(explode('=', $trimmed, 2), 2, '');
            $key = trim($key);
            $value = self::normalizeValue($value);

            if ($key === '') {
                continue;
            }

            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv(sprintf('%s=%s', $key, $value));
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function required(string $key): string
    {
        $value = self::get($key);

        if ($value === null) {
            throw new RuntimeException(sprintf('Missing required environment variable: %s', $key));
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        $normalized = strtolower($value);

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        if (!preg_match('/^-?\d+$/', $value)) {
            throw new RuntimeException(sprintf('Environment variable %s must be an integer.', $key));
        }

        return (int) $value;
    }

    private static function normalizeValue(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        $firstCharacter = $trimmed[0];
        $lastCharacter = $trimmed[strlen($trimmed) - 1];

        if (($firstCharacter === '"' && $lastCharacter === '"') || ($firstCharacter === '\'' && $lastCharacter === '\'')) {
            return substr($trimmed, 1, -1);
        }

        return $trimmed;
    }
}
