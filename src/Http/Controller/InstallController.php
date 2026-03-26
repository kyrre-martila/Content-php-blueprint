<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Application\EnvironmentCheck;
use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\View\TemplateRenderer;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

final class InstallController
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly TemplateRenderer $templateRenderer,
        private readonly ?InstallState $installState,
        private readonly string $migrationsTable = 'phinxlog'
    ) {
    }

    public function show(Request $request): Response
    {
        if ($this->installState?->isInstalled() === true) {
            return Response::redirect('/');
        }

        return $this->renderInstallForm($request);
    }

    public function install(Request $request): Response
    {
        if ($this->installState?->isInstalled() === true) {
            return Response::redirect('/');
        }

        $input = $this->extractInput($request);
        $checks = (new EnvironmentCheck($this->projectRoot))->run();

        $errors = $this->validate($input, $checks);

        if ($errors !== []) {
            return $this->renderInstallForm($request, $input, $errors, null, $checks, 422);
        }

        try {
            $connection = new Connection((new PdoFactory())->create([
                'driver' => 'mysql',
                'host' => $input['db_host'],
                'port' => (int) $input['db_port'],
                'database' => $input['db_name'],
                'username' => $input['db_user'],
                'password' => $input['db_pass'],
                'charset' => 'utf8mb4',
                'options' => [
                    'persistent' => false,
                    'timeout' => 5,
                ],
            ]));
        } catch (Throwable $throwable) {
            $errors['database'] = 'Could not connect to the database with the provided settings.';

            return $this->renderInstallForm($request, $input, $errors, $throwable->getMessage(), $checks, 422);
        }

        try {
            $this->runMigrations($input);
        } catch (Throwable $throwable) {
            $errors['migrations'] = 'Database migrations failed.';

            return $this->renderInstallForm($request, $input, $errors, $throwable->getMessage(), $checks, 500);
        }

        try {
            $repository = new MySqlUserRepository($connection);
            $repository->createInitialAdmin($input['admin_email'], $input['admin_password']);
        } catch (Throwable $throwable) {
            $errors['admin'] = 'Could not create admin user.';

            return $this->renderInstallForm($request, $input, $errors, $throwable->getMessage(), $checks, 500);
        }

        $installState = new InstallState($connection, $this->migrationsTable);

        if (!$installState->isInstalled()) {
            $errors['install_state'] = 'Installation finished but install state could not be verified.';

            return $this->renderInstallForm($request, $input, $errors, null, $checks, 500);
        }

        return Response::redirect('/admin/login');
    }

    /**
     * @return array<string, string>
     */
    private function extractInput(Request $request): array
    {
        $post = $request->postParams();

        return [
            'db_host' => $this->stringValue($post['db_host'] ?? ''),
            'db_port' => $this->stringValue($post['db_port'] ?? ''),
            'db_name' => $this->stringValue($post['db_name'] ?? ''),
            'db_user' => $this->stringValue($post['db_user'] ?? ''),
            'db_pass' => is_string($post['db_pass'] ?? null) ? (string) $post['db_pass'] : '',
            'admin_email' => $this->stringValue($post['admin_email'] ?? ''),
            'admin_password' => is_string($post['admin_password'] ?? null) ? (string) $post['admin_password'] : '',
        ];
    }

    /**
     * @param array<string, string> $input
     * @param array<string, bool> $checks
     * @return array<string, string>
     */
    private function validate(array $input, array $checks): array
    {
        $errors = [];

        foreach (['db_host', 'db_port', 'db_name', 'db_user', 'admin_email', 'admin_password'] as $requiredField) {
            if ($input[$requiredField] === '') {
                $errors[$requiredField] = 'This field is required.';
            }
        }

        if ($input['db_port'] !== '' && !ctype_digit($input['db_port'])) {
            $errors['db_port'] = 'Database port must be a positive integer.';
        }

        if ($input['admin_email'] !== '' && filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['admin_email'] = 'Admin email must be a valid email address.';
        }

        if ($input['admin_password'] !== '' && strlen($input['admin_password']) < 8) {
            $errors['admin_password'] = 'Admin password must contain at least 8 characters.';
        }

        foreach (['php_version', 'pdo', 'pdo_mysql', 'storage_writable'] as $requiredCheck) {
            if (($checks[$requiredCheck] ?? false) !== true) {
                $errors['environment'] = 'Required environment checks failed. Resolve them before installing.';
                break;
            }
        }

        return $errors;
    }

    /**
     * @param array<string, string> $input
     */
    private function runMigrations(array $input): void
    {
        $config = new Config([
            'paths' => [
                'migrations' => $this->projectRoot . '/database/migrations',
                'seeds' => $this->projectRoot . '/database/seeds',
            ],
            'environments' => [
                'default_migration_table' => $this->migrationsTable,
                'default_environment' => 'installer',
                'installer' => [
                    'adapter' => 'mysql',
                    'host' => $input['db_host'],
                    'name' => $input['db_name'],
                    'user' => $input['db_user'],
                    'pass' => $input['db_pass'],
                    'port' => (int) $input['db_port'],
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ],
            'version_order' => 'creation',
        ]);

        $manager = new Manager($config, new ArrayInput([]), new BufferedOutput());
        $manager->migrate('installer');
    }

    /**
     * @param array<string, string> $old
     * @param array<string, string> $errors
     * @param array<string, bool>|null $checks
     */
    private function renderInstallForm(
        Request $request,
        array $old = [],
        array $errors = [],
        ?string $details = null,
        ?array $checks = null,
        int $status = 200
    ): Response {
        $environmentChecks = $checks ?? (new EnvironmentCheck($this->projectRoot))->run();

        $html = $this->templateRenderer->render(
            $this->projectRoot . '/templates/install.php',
            [
                'request' => $request,
                'checks' => $environmentChecks,
                'errors' => $errors,
                'details' => $details,
                'old' => [
                    'db_host' => $old['db_host'] ?? '127.0.0.1',
                    'db_port' => $old['db_port'] ?? '3306',
                    'db_name' => $old['db_name'] ?? '',
                    'db_user' => $old['db_user'] ?? '',
                    'db_pass' => $old['db_pass'] ?? '',
                    'admin_email' => $old['admin_email'] ?? '',
                    'admin_password' => '',
                ],
            ]
        );

        return Response::html($html, $status);
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
