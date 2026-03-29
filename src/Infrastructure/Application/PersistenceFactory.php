<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\CategoryRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Content\MySqlCategoryGroupRepository;
use App\Infrastructure\Content\MySqlCategoryRepository;
use App\Infrastructure\Content\MySqlContentItemRepository;
use App\Infrastructure\Content\MySqlContentTypeRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\PdoFactory;
use App\Domain\Logging\LoggerInterface;
use RuntimeException;

final class PersistenceFactory
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Build persistence services for runtime.
     *
     * Boundary rules:
     * - MySQL bootstrap is required; failures throw immediately.
     * - Runtime never degrades to unavailable/fallback repositories.
     *
     * @return array{
     *   migrationsTable: string,
     *   connection: Connection,
     *   appVersion: AppVersion,
     *   upgradeState: UpgradeState,
     *   installState: InstallState,
     *   installationRequired: false,
     *   persistenceUnavailableReason: null,
     *   repositoriesAvailable: bool,
     *   userRepository: UserRepositoryInterface,
     *   contentItemRepository: ContentItemRepositoryInterface,
     *   contentTypeRepository: ContentTypeRepositoryInterface,
     *   categoryGroupRepository: CategoryGroupRepositoryInterface,
     *   categoryRepository: CategoryRepositoryInterface
     * }
     */
    public function build(): array
    {
        $configuredMigrationsTable = $this->config->get('database.migrations.table', 'phinxlog');
        $migrationsTable = is_string($configuredMigrationsTable) && $configuredMigrationsTable !== ''
            ? $configuredMigrationsTable
            : 'phinxlog';

        $appVersion = new AppVersion($this->config);

        try {
            /** @var array<string, mixed> $connectionConfig */
            $connectionConfig = $this->config->get('database.connections.mysql', []);
            $pdo = (new PdoFactory())->create($connectionConfig);
            $connection = new Connection($pdo);
            $installState = new InstallState($connection, $migrationsTable);
        } catch (\RuntimeException $runtimeException) {
            $this->logger->error('Database bootstrap unavailable; aborting application startup.', [
                'error' => $runtimeException->getMessage(),
            ]);

            throw new RuntimeException('Database connection required but not available', 0, $runtimeException);
        }

        $repositories = $this->buildRepositories($connection);
        $upgradeState = new UpgradeState($appVersion, $connection);

        return [
            'migrationsTable' => $migrationsTable,
            'connection' => $connection,
            'appVersion' => $appVersion,
            'upgradeState' => $upgradeState,
            'installState' => $installState,
            'installationRequired' => false,
            'persistenceUnavailableReason' => null,
            'repositoriesAvailable' => true,
            'userRepository' => $repositories['userRepository'],
            'contentItemRepository' => $repositories['contentItemRepository'],
            'contentTypeRepository' => $repositories['contentTypeRepository'],
            'categoryGroupRepository' => $repositories['categoryGroupRepository'],
            'categoryRepository' => $repositories['categoryRepository'],
        ];
    }

    /**
     * @return array{
     *   userRepository: UserRepositoryInterface,
     *   contentItemRepository: ContentItemRepositoryInterface,
     *   contentTypeRepository: ContentTypeRepositoryInterface,
     *   categoryGroupRepository: CategoryGroupRepositoryInterface,
     *   categoryRepository: CategoryRepositoryInterface
     * }
     */
    private function buildRepositories(Connection $connection): array
    {
        return [
            'userRepository' => new MySqlUserRepository($connection),
            'contentItemRepository' => new MySqlContentItemRepository($connection),
            'contentTypeRepository' => new MySqlContentTypeRepository($connection),
            'categoryGroupRepository' => new MySqlCategoryGroupRepository($connection),
            'categoryRepository' => new MySqlCategoryRepository($connection),
        ];
    }
}
