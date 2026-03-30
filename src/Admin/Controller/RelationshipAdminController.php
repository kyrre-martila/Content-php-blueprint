<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\ContentType;
use App\Domain\Content\ContentItem;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentRelationshipRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplateRenderer;
use InvalidArgumentException;

final class RelationshipAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ContentRelationshipRepositoryInterface $relationships,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $contentTypes = $this->contentTypes->findAll();
        $contentItems = $this->flattenContentItems($this->contentItems->findAllWithTypes()['items'] ?? []);
        $selectedItem = $this->resolveSelectedItem($request);

        $html = $this->templateRenderer->renderTemplate(
            'admin/relationships/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentTypes' => $contentTypes,
                'rules' => $this->relationships->listRelationshipRules(),
                'contentItems' => $contentItems,
                'selectedItem' => $selectedItem,
                'inspectedRelationships' => $selectedItem !== null
                    ? $this->relationships->inspectRelationshipsForItem($selectedItem)
                    : [],
                'success' => $this->session->pullFlash('relationship_success'),
                'error' => $this->session->pullFlash('relationship_error'),
            ]
        );

        return Response::html($html);
    }

    public function storeRule(Request $request): Response
    {
        $post = $request->postParams();
        $fromSlug = is_string($post['from_content_type'] ?? null) ? trim($post['from_content_type']) : '';
        $toSlug = is_string($post['to_content_type'] ?? null) ? trim($post['to_content_type']) : '';
        $relationType = is_string($post['relation_type'] ?? null) ? trim($post['relation_type']) : '';

        if ($fromSlug === '' || $toSlug === '' || $relationType === '') {
            $this->session->flash('relationship_error', 'From type, to type, and relation type are required.');

            return Response::redirect('/admin/relationships');
        }

        $fromType = $this->contentTypes->findByName($fromSlug);
        $toType = $this->contentTypes->findByName($toSlug);

        if (!$fromType instanceof ContentType || !$toType instanceof ContentType) {
            $this->session->flash('relationship_error', 'Selected content types are invalid.');

            return Response::redirect('/admin/relationships');
        }

        if ($this->relationships->isRelationshipAllowed($fromType, $toType, $relationType)) {
            $this->session->flash('relationship_error', 'This relationship rule already exists.');

            return Response::redirect('/admin/relationships');
        }

        try {
            $this->relationships->allowRelationship($fromType, $toType, $relationType);
        } catch (InvalidArgumentException) {
            $this->session->flash('relationship_error', 'Relation type cannot be empty.');

            return Response::redirect('/admin/relationships');
        }

        $this->session->flash('relationship_success', 'Relationship rule saved.');

        return Response::redirect('/admin/relationships');
    }

    public function destroyRule(Request $request): Response
    {
        $post = $request->postParams();
        $fromSlug = is_string($post['from_content_type'] ?? null) ? trim($post['from_content_type']) : '';
        $toSlug = is_string($post['to_content_type'] ?? null) ? trim($post['to_content_type']) : '';
        $relationType = is_string($post['relation_type'] ?? null) ? trim($post['relation_type']) : '';

        if ($fromSlug === '' || $toSlug === '' || $relationType === '') {
            $this->session->flash('relationship_error', 'Unable to remove rule: missing required values.');

            return Response::redirect('/admin/relationships');
        }

        $fromType = $this->contentTypes->findByName($fromSlug);
        $toType = $this->contentTypes->findByName($toSlug);

        if (!$fromType instanceof ContentType || !$toType instanceof ContentType) {
            $this->session->flash('relationship_error', 'Unable to remove rule: invalid content types.');

            return Response::redirect('/admin/relationships');
        }

        $this->relationships->removeRelationshipRule($fromType, $toType, $relationType);
        $this->session->flash('relationship_success', 'Relationship rule removed.');

        return Response::redirect('/admin/relationships');
    }

    /**
     * @param array<string, list<ContentItem>> $groupedItems
     * @return list<ContentItem>
     */
    private function flattenContentItems(array $groupedItems): array
    {
        $flat = [];

        foreach ($groupedItems as $items) {
            foreach ($items as $item) {
                $flat[] = $item;
            }
        }

        usort(
            $flat,
            static fn (ContentItem $a, ContentItem $b): int => strcmp($a->title(), $b->title())
        );

        return $flat;
    }

    private function resolveSelectedItem(Request $request): ?ContentItem
    {
        $itemId = $request->queryParams()['item_id'] ?? null;

        if (!is_string($itemId) || !ctype_digit($itemId)) {
            return null;
        }

        return $this->contentItems->findById((int) $itemId);
    }
}
