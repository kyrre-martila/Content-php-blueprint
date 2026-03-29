<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\View\TemplateRenderer;

final class TemplateAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly AuthSession $authSession,
        private readonly string $projectRoot,
    ) {
    }

    public function index(Request $request): Response
    {
        $contentTypes = $this->contentTypes->findAll();

        $html = $this->templateRenderer->renderTemplate(
            'admin/templates/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'templateGroups' => [
                    'Index' => $this->buildIndexTemplates(),
                    'Content' => $this->buildContentTemplates($contentTypes),
                    'Collections' => $this->buildCollectionTemplates($contentTypes),
                    'System' => $this->buildSystemTemplates(),
                ],
            ]
        );

        return Response::html($html);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildIndexTemplates(): array
    {
        return [
            $this->createTemplateEntry(
                'Default index template',
                'Index',
                'templates/index.php',
                null,
                true,
            ),
        ];
    }

    /**
     * @param list<ContentType> $contentTypes
     * @return list<array<string, mixed>>
     */
    private function buildContentTemplates(array $contentTypes): array
    {
        $entries = [];

        foreach ($contentTypes as $contentType) {
            $entries[] = $this->createTemplateEntry(
                $contentType->label(),
                'Content',
                sprintf('templates/content/%s.php', $contentType->name()),
                'templates/index.php'
            );
        }

        return $entries;
    }

    /**
     * @param list<ContentType> $contentTypes
     * @return list<array<string, mixed>>
     */
    private function buildCollectionTemplates(array $contentTypes): array
    {
        $entries = [];

        foreach ($contentTypes as $contentType) {
            if (!$contentType->isCollectionView()) {
                continue;
            }

            $entries[] = $this->createTemplateEntry(
                $contentType->label(),
                'Collection',
                sprintf('templates/collections/%s.php', $contentType->name()),
                'templates/system/404.php'
            );
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSystemTemplates(): array
    {
        return [
            $this->createTemplateEntry('404', 'System', 'templates/system/404.php', null),
            $this->createTemplateEntry('Search', 'System', 'templates/system/search.php', 'templates/system/404.php'),
        ];
    }

    private function createTemplateEntry(
        string $name,
        string $type,
        string $path,
        ?string $fallbackPath,
        bool $isFallbackRole = false,
    ): array {
        $exists = $this->fileExists($path);
        $fallbackExists = $fallbackPath !== null ? $this->fileExists($fallbackPath) : false;

        $status = 'missing';
        if ($exists) {
            $status = 'exists';
        } elseif ($fallbackPath !== null && $fallbackExists) {
            $status = 'fallback';
        }

        return [
            'name' => $name,
            'type' => $type,
            'path' => $path,
            'status' => $status,
            'fallbackPath' => $fallbackPath,
            'isFallbackRole' => $isFallbackRole,
            'editPath' => '/admin/dev-mode/edit?path=' . rawurlencode($path),
        ];
    }

    private function fileExists(string $relativePath): bool
    {
        return is_file($this->projectRoot . '/' . ltrim($relativePath, '/'));
    }
}
