<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Application\Composition\CompositionExporter;
use App\Application\OCF\OCFExporter;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;

final class ExporterFactory
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    /**
     * @return array{compositionExporter: CompositionExporter, ocfExporter: ?OCFExporter, ocfUnavailable: bool}
     */
    public function build(
        bool $repositoriesAvailable,
        ?ContentTypeRepositoryInterface $contentTypeRepository,
        ?ContentItemRepositoryInterface $contentItemRepository
    ): array {
        $compositionExporter = new CompositionExporter($this->projectRoot);

        if (!$repositoriesAvailable || $contentTypeRepository === null || $contentItemRepository === null) {
            return [
                'compositionExporter' => $compositionExporter,
                'ocfExporter' => null,
                'ocfUnavailable' => true,
            ];
        }

        return [
            'compositionExporter' => $compositionExporter,
            'ocfExporter' => new OCFExporter($contentTypeRepository, $contentItemRepository, $this->projectRoot),
            'ocfUnavailable' => false,
        ];
    }
}
