<?php

declare(strict_types=1);

use App\Admin\Controller\FileAdminController;
use App\Application\Files\DeleteFileService;
use App\Application\Files\FileUploadService;
use App\Application\Files\UpdateFileMetadataService;
use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Http\Request;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Files\FileStorageInterface;
use App\Infrastructure\View\TemplateRenderer;

it('uploads a file through admin controller and upload service path', function (): void {
    $sessionManager = new SessionManager(['name' => 'test_file_admin_upload_session']);
    $authSession = new AuthSession($sessionManager);
    $repository = new InMemoryFileRepository();
    $storage = new InMemoryFileStorage();

    $controller = new FileAdminController(
        new TemplateRenderer(__DIR__ . '/../../../templates'),
        $repository,
        new FileUploadService($repository, $storage),
        new UpdateFileMetadataService($repository),
        new DeleteFileService($repository, $storage),
        $authSession,
        $sessionManager
    );

    $tmpFile = tempnam(sys_get_temp_dir(), 'file-upload-');
    file_put_contents($tmpFile, 'quarterly report');

    $request = new Request('POST', '/admin/files/upload', [], [
        'visibility' => 'private',
    ], [], [
        'file' => [
            'name' => 'report.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 16,
        ],
    ], []);

    $response = $controller->storeUpload($request);

    expect($response->status())->toBe(302)
        ->and($response->header('Location'))->toBe('/admin/files')
        ->and($repository->findAll())->toHaveCount(1)
        ->and($storage->writes)->toHaveCount(1);

    unlink($tmpFile);
});

it('rejects invalid upload metadata for empty uploads', function (): void {
    $sessionManager = new SessionManager(['name' => 'test_file_admin_invalid_upload_session']);
    $authSession = new AuthSession($sessionManager);
    $repository = new InMemoryFileRepository();
    $storage = new InMemoryFileStorage();

    $controller = new FileAdminController(
        new TemplateRenderer(__DIR__ . '/../../../templates'),
        $repository,
        new FileUploadService($repository, $storage),
        new UpdateFileMetadataService($repository),
        new DeleteFileService($repository, $storage),
        $authSession,
        $sessionManager
    );

    $request = new Request('POST', '/admin/files/upload', [], [
        'visibility' => 'private',
    ], [], [
        'file' => [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_OK,
            'size' => 0,
        ],
    ], []);

    $response = $controller->storeUpload($request);

    expect($response->status())->toBe(422)
        ->and($repository->findAll())->toHaveCount(0)
        ->and($storage->writes)->toBe([]);
});

it('deletes file record and storage object through delete service', function (): void {
    $repository = new InMemoryFileRepository();
    $storage = new InMemoryFileStorage();

    $created = $repository->save(new FileAsset(
        id: null,
        originalName: 'manual.pdf',
        storedName: 'manual-aabbccddeeff.pdf',
        slug: 'manual',
        mimeType: 'application/pdf',
        extension: 'pdf',
        sizeBytes: 111,
        visibility: FileVisibility::Private,
        storageDisk: 'local',
        storagePath: 'ab/cd/manual-aabbccddeeff.pdf',
        checksumSha256: str_repeat('a', 64),
        uploadedByUserId: 1,
        createdAt: new \DateTimeImmutable('2026-04-09 00:00:00'),
        updatedAt: new \DateTimeImmutable('2026-04-09 00:00:00')
    ));

    $storage->write($created->storagePath(), 'manual-content');

    $deleted = (new DeleteFileService($repository, $storage))->deleteById((int) $created->id());

    expect($deleted)->toBeTrue()
        ->and($repository->findById((int) $created->id()))->toBeNull()
        ->and($storage->deleted)->toContain($created->storagePath());
});

final class InMemoryFileRepository implements FileRepositoryInterface
{
    /** @var array<int, FileAsset> */
    private array $files = [];
    private int $nextId = 1;

    public function save(FileAsset $fileAsset): FileAsset
    {
        $id = $fileAsset->id() ?? $this->nextId++;
        $persisted = new FileAsset(
            id: $id,
            originalName: $fileAsset->originalName(),
            storedName: $fileAsset->storedName(),
            slug: $fileAsset->slug(),
            mimeType: $fileAsset->mimeType(),
            extension: $fileAsset->extension(),
            sizeBytes: $fileAsset->sizeBytes(),
            visibility: $fileAsset->visibility(),
            storageDisk: $fileAsset->storageDisk(),
            storagePath: $fileAsset->storagePath(),
            checksumSha256: $fileAsset->checksumSha256(),
            uploadedByUserId: $fileAsset->uploadedByUserId(),
            createdAt: $fileAsset->createdAt(),
            updatedAt: $fileAsset->updatedAt()
        );

        $this->files[$id] = $persisted;

        return $persisted;
    }

    public function findById(int $id): ?FileAsset
    {
        return $this->files[$id] ?? null;
    }

    public function findBySlug(string $slug): ?FileAsset
    {
        foreach ($this->files as $file) {
            if ($file->slug() === $slug) {
                return $file;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->files);
    }

    public function delete(FileAsset $fileAsset): void
    {
        if ($fileAsset->id() !== null) {
            unset($this->files[$fileAsset->id()]);
        }
    }
}

final class InMemoryFileStorage implements FileStorageInterface
{
    /** @var array<string, string> */
    public array $writes = [];
    /** @var list<string> */
    public array $deleted = [];

    public function write(string $storagePath, string $contents): void
    {
        $this->writes[$storagePath] = $contents;
    }

    public function read(string $storagePath): string
    {
        return $this->writes[$storagePath] ?? '';
    }

    public function exists(string $storagePath): bool
    {
        return array_key_exists($storagePath, $this->writes);
    }

    public function delete(string $storagePath): void
    {
        unset($this->writes[$storagePath]);
        $this->deleted[] = $storagePath;
    }

    public function absolutePath(string $storagePath): string
    {
        return $storagePath;
    }
}
