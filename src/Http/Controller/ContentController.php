<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Content\Exception\InvalidSlugException;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class ContentController
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly TemplateResolver $templateResolver,
        private readonly TemplateRenderer $templateRenderer,
        private readonly EditorMode $editorMode
    ) {
    }

    public function show(Request $request): Response
    {
        $slugInput = $request->attribute('slug');

        if (!is_string($slugInput) || trim($slugInput) === '') {
            return $this->renderNotFound($request);
        }

        try {
            $slug = Slug::fromString($slugInput);
        } catch (InvalidSlugException) {
            return $this->renderNotFound($request);
        }

        $contentItem = $this->contentItems->findBySlug($slug);

        if ($contentItem === null || !$contentItem->isPublished()) {
            return $this->renderNotFound($request);
        }

        $templatePath = $this->templateResolver->resolveContentTemplate();
        $contentSummary = $this->buildSummaryFromPatternBlocks($contentItem->patternBlocks());
        $metaTitle = $contentItem->metaTitle();
        $metaDescription = $contentItem->metaDescription() ?? ($contentSummary !== '' ? $contentSummary : null);

        $html = $this->templateRenderer->render($templatePath, [
            'contentItem' => $contentItem,
            'request' => $request,
            'slug' => $slug->value(),
            'patternBlocks' => $contentItem->patternBlocks(),
            'meta' => [
                'title' => $metaTitle ?? $contentItem->title(),
                'description' => $metaDescription,
                'og_image' => $contentItem->ogImage(),
                'canonical' => $contentItem->canonicalUrl(),
                'noindex' => $contentItem->noindex(),
            ],
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);

        return Response::html($html);
    }

    private function renderNotFound(Request $request): Response
    {
        $html = $this->templateRenderer->render($this->templateResolver->resolveNotFound(), [
            'request' => $request,
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);

        return Response::html($html, 404);
    }

    /**
     * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
     */
    private function buildSummaryFromPatternBlocks(array $patternBlocks): string
    {
        $parts = [];

        foreach ($patternBlocks as $block) {
            foreach ($block['data'] as $value) {
                if (!is_string($value)) {
                    continue;
                }

                $trimmed = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

                if ($trimmed === '') {
                    continue;
                }

                $parts[] = $trimmed;
            }
        }

        if ($parts === []) {
            return '';
        }

        $summary = implode(' ', $parts);

        return mb_substr($summary, 0, 160);
    }
}
