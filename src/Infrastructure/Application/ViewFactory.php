<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Editor\EditableFieldRenderer;
use App\Infrastructure\Pattern\PatternDataValidator;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\View\PatternRenderer;
use App\Infrastructure\View\TemplatePathMap;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class ViewFactory
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $siteUrl,
        private readonly string $siteName
    ) {
    }

    /**
     * @return array{
     *   templatePathMap: TemplatePathMap,
     *   templateResolver: TemplateResolver,
     *   editableFieldRenderer: EditableFieldRenderer,
     *   patternRegistry: PatternRegistry,
     *   patternRenderer: PatternRenderer,
     *   templateRenderer: TemplateRenderer
     * }
     */
    public function build(): array
    {
        $templatesPath = $this->projectRoot . '/templates';
        $patternRegistry = new PatternRegistry($this->projectRoot . '/patterns');
        $templatePathMap = new TemplatePathMap();
        $templateResolver = new TemplateResolver($templatesPath, $templatePathMap);

        $editableFieldRenderer = new EditableFieldRenderer();
        $patternDataValidator = new PatternDataValidator();
        $patternRenderer = new PatternRenderer($patternRegistry, $patternDataValidator, $editableFieldRenderer);

        $templateRenderer = new TemplateRenderer(
            $templatesPath,
            $patternRenderer,
            $editableFieldRenderer,
            $this->siteUrl,
            $this->siteName
        );

        return [
            'templatePathMap' => $templatePathMap,
            'templateResolver' => $templateResolver,
            'editableFieldRenderer' => $editableFieldRenderer,
            'patternRegistry' => $patternRegistry,
            'patternRenderer' => $patternRenderer,
            'templateRenderer' => $templateRenderer,
        ];
    }
}
