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
            return Response::html('<h1>404 Not Found</h1>', 404);
        }

        try {
            $slug = Slug::fromString($slugInput);
        } catch (InvalidSlugException) {
            return Response::html('<h1>404 Not Found</h1>', 404);
        }

        $contentItem = $this->contentItems->findBySlug($slug);

        if ($contentItem === null || !$contentItem->isPublished()) {
            return Response::html('<h1>404 Not Found</h1>', 404);
        }

        $templatePath = $this->templateResolver->resolveForSlug($slug->value());

        $html = $this->templateRenderer->render($templatePath, [
            'contentItem' => $contentItem,
            'request' => $request,
            'slug' => $slug->value(),
            'patternBlocks' => $contentItem->patternBlocks(),
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);

        return Response::html($html);
    }
}
