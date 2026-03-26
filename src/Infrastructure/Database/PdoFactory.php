<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

final class PdoFactory
{
    /**
     * @param array<string, mixed> $connectionConfig
     */
    public function create(array $connectionConfig): PDO
    {
        $driver = $connectionConfig['driver'] ?? null;

        if (!is_string($driver) || $driver !== 'mysql') {
            throw new InvalidArgumentException('Only mysql driver is supported.');
        }

        $host = $this->stringConfig($connectionConfig, 'host', '127.0.0.1');
        $port = $this->intConfig($connectionConfig, 'port', 3306);
        $database = $this->stringConfig($connectionConfig, 'database');
        $username = $this->stringConfig($connectionConfig, 'username');
        $password = $this->stringConfig($connectionConfig, 'password', '');
        $charset = $this->stringConfig($connectionConfig, 'charset', 'utf8mb4');

        if ($database === '' || $username === '') {
            throw new InvalidArgumentException('Database name and username are required for mysql connections.');
        }

        $rawOptions = $connectionConfig['options'] ?? [];
        $options = is_array($rawOptions) ? $rawOptions : [];
        /** @var array<string, mixed> $options */

        $persistent = $this->boolConfig($options, 'persistent', false);
        $timeout = $this->intConfig($options, 'timeout', 5);

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => $persistent,
                PDO::ATTR_TIMEOUT => $timeout,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                sprintf('Could not establish database connection to %s:%d/%s.', $host, $port, $database),
                previous: $exception,
            );
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function stringConfig(array $config, string $key, string $default = ''): string
    {
        $value = $config[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function intConfig(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function boolConfig(array $config, string $key, bool $default): bool
    {
        $value = $config[$key] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return $default;
    }
}
