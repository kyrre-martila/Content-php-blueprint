<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Security\AdminAccessPolicy;
use App\Domain\Auth\Role;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Application\UpgradeState;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplatePathMap;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class DashboardController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly AuthSession $authSession,
        private readonly UpgradeState $upgradeState,
        private readonly TemplateResolver $templateResolver,
        private readonly TemplatePathMap $templatePathMap,
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
            $contentTemplatePath = $this->templatePathMap->contentTemplate($contentType);

            if (!$this->templateResolver->templateExists($contentTemplatePath)) {
                $missingContentTemplateCount++;
            }

            if ($contentType->isCollectionView()) {
                $collectionTemplatePath = $this->templatePathMap->collectionTemplate($contentType);

                if (!$this->templateResolver->templateExists($collectionTemplatePath)) {
                    $missingContentTemplateCount++;
                }
            }
        }

        $systemTemplatePaths = [
            $this->templatePathMap->systemTemplate('404'),
            $this->templatePathMap->systemTemplate('search'),
        ];

        $missingSystemTemplateCount = 0;
        foreach ($systemTemplatePaths as $systemTemplatePath) {
            if (!$this->templateResolver->templateExists($systemTemplatePath)) {
                $missingSystemTemplateCount++;
            }
        }

        $indexTemplateExists = $this->templateResolver->templateExists($this->templatePathMap->indexFallbackTemplate());
        $currentRole = $this->authSession->role() ?? Role::editor();
        $accessPolicy = new AdminAccessPolicy();

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
                'quickActions' => array_values(array_filter([
                    $accessPolicy->canAccessSystemManagement($currentRole) ? [
                        'label' => 'Create Content Type',
                        'href' => '/admin/content-types/create',
                        'class' => 'admin-action admin-action--primary',
                    ] : null,
                    $accessPolicy->canAccessSystemManagement($currentRole) ? [
                        'label' => 'Open Template Manager',
                        'href' => '/admin/templates',
                        'class' => 'admin-action admin-action--primary',
                    ] : null,
                    $accessPolicy->canAccessSystemManagement($currentRole) ? [
                        'label' => 'Open System Templates',
                        'href' => '/admin/system-templates',
                        'class' => 'admin-action admin-action--primary',
                    ] : null,
                    $accessPolicy->canAccessContentManagement($currentRole) ? [
                        'label' => 'Create Content Item',
                        'href' => '/admin/content/create',
                        'class' => 'admin-action admin-action--primary',
                    ] : null,
                    $accessPolicy->canAccessFileLibrary($currentRole) ? [
                        'label' => 'Open Files Library',
                        'href' => '/admin/files',
                        'class' => 'admin-action admin-action--primary',
                    ] : null,
                ])),
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
