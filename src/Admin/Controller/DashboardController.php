<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Application\UpgradeState;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class DashboardController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly AuthSession $authSession,
        private readonly UpgradeState $upgradeState,
        private readonly TemplateResolver $templateResolver,
        private readonly ?ContentTypeRepositoryInterface $contentTypeRepository,
        private readonly ?EditorMode $editorMode = null,
        private readonly ?DevMode $devMode = null
    ) {
    }

    public function index(Request $request): Response
    {
        $contentTypes = $this->contentTypeRepository?->findAll() ?? [];

        $collectionTypeCount = 0;
        foreach ($contentTypes as $contentType) {
            if ($contentType->isCollectionView()) {
                $collectionTypeCount++;
            }
        }

        $totalTypeCount = count($contentTypes);
        $singleTypeCount = $totalTypeCount - $collectionTypeCount;

        $missingContentTemplateCount = 0;
        foreach ($contentTypes as $contentType) {
            $contentTemplatePath = sprintf('templates/content/%s.php', $contentType->name());

            if (!$this->templateResolver->templateExists($contentTemplatePath)) {
                $missingContentTemplateCount++;
            }

            if ($contentType->isCollectionView()) {
                $collectionTemplatePath = sprintf('templates/collections/%s.php', $contentType->name());

                if (!$this->templateResolver->templateExists($collectionTemplatePath)) {
                    $missingContentTemplateCount++;
                }
            }
        }

        $systemTemplatePaths = [
            'templates/system/404.php',
            'templates/system/search.php',
        ];

        $missingSystemTemplateCount = 0;
        foreach ($systemTemplatePaths as $systemTemplatePath) {
            if (!$this->templateResolver->templateExists($systemTemplatePath)) {
                $missingSystemTemplateCount++;
            }
        }

        $indexTemplateExists = $this->templateResolver->templateExists('templates/index.php');

        $html = $this->templateRenderer->renderTemplate(
            'admin/dashboard.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'editorModeActive' => $this->editorMode?->isActive() ?? false,
                'editorCanUse' => $this->editorMode?->canUse() ?? false,
                'devModeActive' => $this->devMode?->isActive() ?? false,
                'devModeCanUse' => $this->devMode?->canUse() ?? false,
                'upgradeRequired' => $this->upgradeState->isUpgradeRequired(),
                'currentVersion' => $this->upgradeState->currentVersion(),
                'installedVersion' => $this->upgradeState->installedVersion(),
                'quickActions' => [
                    [
                        'label' => 'Create Content Type',
                        'href' => '/admin/content-types/create',
                        'class' => 'admin-action admin-action--primary',
                    ],
                    [
                        'label' => 'Open Template Manager',
                        'href' => '/admin/templates',
                        'class' => 'admin-action admin-action--primary',
                    ],
                    [
                        'label' => 'Open System Templates',
                        'href' => '/admin/system-templates',
                        'class' => 'admin-action admin-action--primary',
                    ],
                    [
                        'label' => 'Create Content Item',
                        'href' => '#',
                        'class' => 'admin-action admin-action--primary',
                        'isPlaceholder' => true,
                    ],
                ],
                'templateStatus' => [
                    'indexTemplateStatus' => $indexTemplateExists ? 'Available' : 'Missing',
                    'missingContentTemplateCount' => $missingContentTemplateCount,
                    'missingSystemTemplateCount' => $missingSystemTemplateCount,
                ],
                'contentTypeSummary' => [
                    'total' => $totalTypeCount,
                    'collections' => $collectionTypeCount,
                    'singles' => $singleTypeCount,
                ],
            ]
        );

        return Response::html($html);
    }
}
