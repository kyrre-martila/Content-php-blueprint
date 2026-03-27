<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Content\MySqlContentItemRepository;
use App\Infrastructure\Content\MySqlContentTypeRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Logging\Logger;

final class PersistenceFactory
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array{
     *   migrationsTable: string,
     *   connection: ?Connection,
     *   installState: ?InstallState,
     *   installationRequired: bool,
     *   userRepository: UserRepositoryInterface,
     *   contentItemRepository: ?ContentItemRepositoryInterface,
     *   contentTypeRepository: ?ContentTypeRepositoryInterface
     * }
     */
    public function build(): array
    {
        /** @var string $migrationsTable */
        $migrationsTable = (string) $this->config->get('database.migrations.table', 'phinxlog');

        $connection = null;
        $installState = null;
        $installationRequired = false;

        try {
            /** @var array<string, mixed> $connectionConfig */
            $connectionConfig = $this->config->get('database.connections.mysql', []);
            $pdo = (new PdoFactory())->create($connectionConfig);
            $connection = new Connection($pdo);
            $installState = new InstallState($connection, $migrationsTable);
        } catch (\RuntimeException $runtimeException) {
            $installationRequired = true;
            $this->logger->warning('Database bootstrap unavailable, forcing install flow.', [
                'error' => $runtimeException->getMessage(),
            ]);
        }

        $repositories = $this->buildRepositories($connection);

        return [
            'migrationsTable' => $migrationsTable,
            'connection' => $connection,
            'installState' => $installState,
            'installationRequired' => $installationRequired,
            'userRepository' => $repositories['userRepository'],
            'contentItemRepository' => $repositories['contentItemRepository'],
            'contentTypeRepository' => $repositories['contentTypeRepository'],
        ];
    }

    /**
     * @return array{
     *   userRepository: UserRepositoryInterface,
     *   contentItemRepository: ?ContentItemRepositoryInterface,
     *   contentTypeRepository: ?ContentTypeRepositoryInterface
     * }
     */
    private function buildRepositories(?Connection $connection): array
    {
        if ($connection === null) {
            $temporaryConnection = new Connection((new \PDO('sqlite::memory:')));

            return [
                'userRepository' => new MySqlUserRepository($temporaryConnection),
                'contentItemRepository' => null,
                'contentTypeRepository' => null,
            ];
        }

        return [
            'userRepository' => new MySqlUserRepository($connection),
            'contentItemRepository' => new MySqlContentItemRepository($connection),
            'contentTypeRepository' => new MySqlContentTypeRepository($connection),
        ];
    }
}
