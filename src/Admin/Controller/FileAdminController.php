<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\Files\DeleteFileService;
use App\Application\Files\FileUploadService;
use App\Application\Files\UpdateFileMetadataService;
use App\Application\Files\UploadedFileInput;
use App\Domain\Files\FileAsset;
use App\Domain\Files\FileVisibility;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplateRenderer;
use InvalidArgumentException;

final class FileAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly FileUploadService $uploadService,
        private readonly UpdateFileMetadataService $updateMetadataService,
        private readonly DeleteFileService $deleteFileService,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $rows = array_map(fn ($file): array => [
            'id' => $file->id(),
            'original_name' => $file->originalName(),
            'mime_type' => $file->mimeType(),
            'extension' => $file->extension(),
            'size_bytes' => $file->sizeBytes(),
            'visibility' => $file->visibility()->value,
            'uploaded_by' => $file->uploadedByUserId(),
            'created_at' => $file->createdAt()->format('Y-m-d H:i:s'),
            'edit_path' => '/admin/files/' . $file->id() . '/edit',
            'delete_path' => '/admin/files/' . $file->id(),
        ], $this->fileRepository->findAll());

        $html = $this->templateRenderer->renderTemplate('admin/files/index.php', [
            'request' => $request,
            'authUser' => $this->authSession->user(),
            'rows' => $rows,
            'success' => $this->session->pullFlash('file_success'),
            'error' => $this->session->pullFlash('file_error'),
        ]);

        return Response::html($html);
    }

    public function upload(Request $request): Response
    {
        return $this->renderUploadForm($request, ['errors' => []]);
    }

    public function storeUpload(Request $request): Response
    {
        $visibility = $this->resolveVisibility($request->postParams()['visibility'] ?? null);
        $uploadedFile = $this->extractUploadedFile($request->files()['file'] ?? null);

        if (is_string($visibility)) {
            return $this->renderUploadForm($request, ['errors' => ['visibility' => $visibility]], 422);
        }

        if (is_string($uploadedFile)) {
            return $this->renderUploadForm($request, ['errors' => ['file' => $uploadedFile]], 422);
        }

        try {
            $this->uploadService->upload(new UploadedFileInput(
                originalName: $uploadedFile['name'],
                mimeType: $uploadedFile['type'],
                extension: strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION)),
                sizeBytes: $uploadedFile['size'],
                contents: $uploadedFile['contents'],
                visibility: $visibility,
                uploadedByUserId: $this->authSession->user()['id'] ?? null
            ));
        } catch (InvalidArgumentException $exception) {
            return $this->renderUploadForm($request, ['errors' => ['file' => $exception->getMessage()]], 422);
        }

        $this->session->flash('file_success', 'File uploaded successfully.');

        return Response::redirect('/admin/files');
    }

    public function edit(Request $request): Response
    {
        $file = $this->resolveFileFromRequest($request);

        if ($file === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        return $this->renderEditForm($request, $file, [
            'errors' => [],
            'old' => [
                'slug' => $file->slug(),
                'visibility' => $file->visibility()->value,
            ],
        ]);
    }

    public function update(Request $request): Response
    {
        $file = $this->resolveFileFromRequest($request);

        if ($file === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $slug = is_string($request->postParams()['slug'] ?? null) ? (string) $request->postParams()['slug'] : '';
        $visibility = $this->resolveVisibility($request->postParams()['visibility'] ?? null);

        if (is_string($visibility)) {
            return $this->renderEditForm($request, $file, [
                'errors' => ['visibility' => $visibility],
                'old' => ['slug' => $slug, 'visibility' => (string) ($request->postParams()['visibility'] ?? '')],
            ], 422);
        }

        try {
            $this->updateMetadataService->update($file, $slug, $visibility);
        } catch (InvalidArgumentException $exception) {
            return $this->renderEditForm($request, $file, [
                'errors' => ['slug' => $exception->getMessage()],
                'old' => ['slug' => $slug, 'visibility' => $visibility->value],
            ], 422);
        }

        $this->session->flash('file_success', 'File updated successfully.');

        return Response::redirect('/admin/files');
    }

    public function destroy(Request $request): Response
    {
        if (!$this->isDeleteMethod($request) || !$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 400);
        }

        $id = $request->attribute('id');

        if (!is_string($id) || !ctype_digit($id)) {
            return Response::json(['success' => false], 404);
        }

        if (!$this->deleteFileService->deleteById((int) $id)) {
            return Response::json(['success' => false], 404);
        }

        $this->session->flash('file_success', 'File deleted successfully.');

        return Response::redirect('/admin/files');
    }

    private function renderUploadForm(Request $request, array $context, int $status = 200): Response
    {
        $html = $this->templateRenderer->renderTemplate('admin/files/upload.php', [
            'request' => $request,
            'authUser' => $this->authSession->user(),
            ...$context,
        ]);

        return Response::html($html, $status);
    }

    private function renderEditForm(Request $request, FileAsset $file, array $context, int $status = 200): Response
    {
        $html = $this->templateRenderer->renderTemplate('admin/files/edit.php', [
            'request' => $request,
            'authUser' => $this->authSession->user(),
            'file' => $file,
            ...$context,
        ]);

        return Response::html($html, $status);
    }

    private function resolveFileFromRequest(Request $request): ?FileAsset
    {
        $id = $request->attribute('id');

        if (!is_string($id) || !ctype_digit($id)) {
            return null;
        }

        return $this->fileRepository->findById((int) $id);
    }

    private function resolveVisibility(mixed $rawVisibility): FileVisibility|string
    {
        if (!is_string($rawVisibility) || trim($rawVisibility) === '') {
            return 'Visibility is required.';
        }

        try {
            return FileVisibility::fromString($rawVisibility);
        } catch (InvalidArgumentException) {
            return 'Visibility must be public, authenticated, or private.';
        }
    }

    /**
     * @return array{name: string, type: string, size: int, contents: string}|string
     */
    private function extractUploadedFile(mixed $uploaded): array|string
    {
        if (!is_array($uploaded)) {
            return 'Please select a file to upload.';
        }

        $name = is_string($uploaded['name'] ?? null) ? trim((string) $uploaded['name']) : '';
        $type = is_string($uploaded['type'] ?? null) ? trim((string) $uploaded['type']) : '';
        $size = is_int($uploaded['size'] ?? null) ? (int) $uploaded['size'] : -1;
        $error = is_int($uploaded['error'] ?? null) ? (int) $uploaded['error'] : UPLOAD_ERR_OK;
        $tmpName = is_string($uploaded['tmp_name'] ?? null) ? (string) $uploaded['tmp_name'] : '';

        if ($error !== UPLOAD_ERR_OK) {
            return 'Upload failed. Please retry.';
        }

        if ($name === '' || $type === '' || $tmpName === '') {
            return 'Uploaded file metadata is invalid.';
        }

        if ($size < 1) {
            return 'Uploaded file cannot be empty.';
        }

        $contents = file_get_contents($tmpName);

        if (!is_string($contents) || $contents === '') {
            return 'Uploaded file could not be read.';
        }

        return [
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'contents' => $contents,
        ];
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        $token = $request->postParams()['_csrf_token'] ?? null;

        return is_string($token) && $token !== '' && $this->session->tokenMatches($token);
    }

    private function isDeleteMethod(Request $request): bool
    {
        if ($request->method() === 'DELETE') {
            return true;
        }

        $methodOverride = $request->postParams()['_method'] ?? null;

        return is_string($methodOverride) && strtoupper($methodOverride) === 'DELETE';
    }
}
