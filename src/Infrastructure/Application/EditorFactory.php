<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Application\DevMode\DevFileService;
use App\Application\Editor\EditorContentService;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditableFieldValidator;
use App\Infrastructure\Editor\EditableFileRegistry;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\Editor\EditHistoryLogger;
use App\Infrastructure\Pattern\PatternRegistry;

final class EditorFactory
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    /**
     * @return array{
     *   editorMode: EditorMode,
     *   devMode: DevMode,
     *   editableFieldValidator: ?EditableFieldValidator,
     *   editorContentService: ?EditorContentService,
     *   devFileService: DevFileService,
     *   devModeFiles: EditableFileRegistry,
     *   devModeHistory: EditHistoryLogger
     * }
     */
    public function build(
        AuthSession $authSession,
        SessionManager $sessionManager,
        ?ContentItemRepositoryInterface $contentItemRepository,
        PatternRegistry $patternRegistry
    ): array {
        $editorMode = new EditorMode($authSession, $sessionManager);
        $devMode = new DevMode($this->projectRoot, $authSession, $sessionManager);

        $editableFieldValidator = null;
        $editorContentService = null;

        if ($contentItemRepository !== null) {
            $editableFieldValidator = new EditableFieldValidator(
                $editorMode,
                $contentItemRepository,
                $patternRegistry
            );

            $editorContentService = new EditorContentService(
                $contentItemRepository,
                $editableFieldValidator
            );
        }

        return [
            'editorMode' => $editorMode,
            'devMode' => $devMode,
            'editableFieldValidator' => $editableFieldValidator,
            'editorContentService' => $editorContentService,
            'devFileService' => new DevFileService($this->projectRoot),
            'devModeFiles' => new EditableFileRegistry($this->projectRoot, $devMode),
            'devModeHistory' => new EditHistoryLogger($this->projectRoot . '/storage/logs/dev-mode-edits.log'),
        ];
    }
}
