<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Auth\UnavailableUserRepository;
use App\Infrastructure\Config\ConfigRepository;
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
     * Build persistence services for runtime.
     *
     * Boundary rules:
     * - If MySQL bootstrap fails, we DO NOT create temporary/fake repositories.
     * - Database-backed repositories are marked unavailable and callers must gate construction.
     *
     * @return array{
     *   migrationsTable: string,
     *   connection: ?Connection,
     *   appVersion: AppVersion,
     *   upgradeState: UpgradeState,
     *   installState: ?InstallState,
     *   installationRequired: bool,
     *   persistenceUnavailableReason: ?string,
     *   repositoriesAvailable: bool,
     *   userRepository: UserRepositoryInterface,
     *   contentItemRepository: ?ContentItemRepositoryInterface,
     *   contentTypeRepository: ?ContentTypeRepositoryInterface
     * }
     */
    public function build(): array
    {
        $configuredMigrationsTable = $this->config->get('database.migrations.table', 'phinxlog');
        $migrationsTable = is_string($configuredMigrationsTable) && $configuredMigrationsTable !== ''
            ? $configuredMigrationsTable
            : 'phinxlog';

        $appVersion = new AppVersion($this->config);

        $connection = null;
        $installState = null;
        $installationRequired = false;
        $persistenceUnavailableReason = null;

        try {
            /** @var array<string, mixed> $connectionConfig */
            $connectionConfig = $this->config->get('database.connections.mysql', []);
            $pdo = (new PdoFactory())->create($connectionConfig);
            $connection = new Connection($pdo);
            $installState = new InstallState($connection, $migrationsTable);
        } catch (\RuntimeException $runtimeException) {
            $installationRequired = true;
            $persistenceUnavailableReason = $runtimeException->getMessage();

            $this->logger->warning('Database bootstrap unavailable; database-backed repositories are disabled.', [
                'error' => $persistenceUnavailableReason,
            ]);
        }

        $repositories = $this->buildRepositories($connection, $persistenceUnavailableReason);
        $upgradeState = new UpgradeState($appVersion, $connection);

        return [
            'migrationsTable' => $migrationsTable,
            'connection' => $connection,
            'appVersion' => $appVersion,
            'upgradeState' => $upgradeState,
            'installState' => $installState,
            'installationRequired' => $installationRequired,
            'persistenceUnavailableReason' => $persistenceUnavailableReason,
            'repositoriesAvailable' => $connection !== null,
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
    private function buildRepositories(?Connection $connection, ?string $unavailableReason): array
    {
        if ($connection === null) {
            return [
                'userRepository' => new UnavailableUserRepository(
                    $unavailableReason ?? 'Database bootstrap failed before repositories were available.'
                ),
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
