<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOStatement;

final class Connection
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->prepareAndExecute($sql, $params);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return $rows;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $result = $statement->fetch();

        if ($result === false) {
            return null;
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->prepareAndExecute($sql, $params);

        return $statement->rowCount();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function insertAndGetId(string $sql, array $params = []): string
    {
        $this->prepareAndExecute($sql, $params);

        $lastInsertId = $this->pdo->lastInsertId();

        return $lastInsertId === false ? '' : $lastInsertId;
    }

    /**
     * @template TReturn
     * @param callable(self): TReturn $callback
     * @return TReturn
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function prepareAndExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $parameter = str_starts_with($key, ':') ? $key : ':' . $key;
            $statement->bindValue($parameter, $value, $this->detectPdoType($value));
        }

        $statement->execute();

        return $statement;
    }

    private function detectPdoType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}
