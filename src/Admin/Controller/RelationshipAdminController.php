<?php

declare(strict_types=1);

namespace App\Admin\Controller;

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
        $types = $this->contentTypes->findAll();
        $rules = $this->relationships->findRelationshipRules();

        $itemOptions = [];
        $allItems = $this->contentItems->findAllWithTypes()['items'] ?? [];
        foreach ($allItems as $typeItems) {
            foreach ($typeItems as $item) {
                $itemId = $item->id();
                if (!is_int($itemId)) {
                    continue;
                }

                $itemOptions[] = [
                    'id' => $itemId,
                    'label' => sprintf('%s (%s)', $item->title(), $item->type()->name()),
                ];
            }
        }

        usort(
            $itemOptions,
            static fn (array $left, array $right): int => strcmp((string) $left['label'], (string) $right['label'])
        );

        $selectedItemId = $this->queryInt($request, 'item');
        $relationshipRows = [];
        if ($selectedItemId !== null) {
            $selectedItem = $this->contentItems->findById($selectedItemId);

            if ($selectedItem !== null) {
                $outgoing = $this->relationships->findOutgoingRelationships($selectedItem);
                $incoming = $this->relationships->findIncomingRelationships($selectedItem);

                foreach ($outgoing as $relationship) {
                    $relationshipRows[] = [
                        'from_item' => $relationship->fromContentItemTitle(),
                        'to_item' => $relationship->toContentItemTitle(),
                        'relation_type' => $relationship->relationType(),
                        'sort_order' => $relationship->sortOrder(),
                    ];
                }

                foreach ($incoming as $relationship) {
                    $relationshipRows[] = [
                        'from_item' => $relationship->fromContentItemTitle(),
                        'to_item' => $relationship->toContentItemTitle(),
                        'relation_type' => $relationship->relationType(),
                        'sort_order' => $relationship->sortOrder(),
                    ];
                }
            }
        }

        usort(
            $relationshipRows,
            static fn (array $left, array $right): int => [$left['relation_type'], $left['from_item'], $left['to_item']]
                <=> [$right['relation_type'], $right['from_item'], $right['to_item']]
        );

        $html = $this->templateRenderer->renderTemplate('admin/relationships/index.php', [
            'request' => $request,
            'authUser' => $this->authSession->user(),
            'contentTypes' => $types,
            'rules' => $rules,
            'itemOptions' => $itemOptions,
            'selectedItemId' => $selectedItemId,
            'relationshipRows' => $relationshipRows,
            'success' => $this->session->pullFlash('relationship_success'),
            'error' => $this->session->pullFlash('relationship_error'),
        ]);

        return Response::html($html);
    }

    public function storeRule(Request $request): Response
    {
        $post = $request->postParams();
        $fromTypeName = is_string($post['from_type'] ?? null) ? trim($post['from_type']) : '';
        $toTypeName = is_string($post['to_type'] ?? null) ? trim($post['to_type']) : '';
        $relationType = is_string($post['relation_type'] ?? null) ? trim($post['relation_type']) : '';

        if ($fromTypeName === '' || $toTypeName === '' || $relationType === '') {
            $this->session->flash('relationship_error', 'From type, to type, and relation type are required.');

            return Response::redirect('/admin/relationships');
        }

        $fromType = $this->contentTypes->findByName($fromTypeName);
        $toType = $this->contentTypes->findByName($toTypeName);

        if ($fromType === null || $toType === null) {
            $this->session->flash('relationship_error', 'Select valid content types for the relationship rule.');

            return Response::redirect('/admin/relationships');
        }

        if ($this->relationships->isRelationshipAllowed($fromType, $toType, $relationType)) {
            $this->session->flash('relationship_error', 'That relationship rule already exists.');

            return Response::redirect('/admin/relationships');
        }

        try {
            $this->relationships->allowRelationship($fromType, $toType, $relationType);
        } catch (InvalidArgumentException) {
            $this->session->flash('relationship_error', 'Relation type cannot be empty.');

            return Response::redirect('/admin/relationships');
        }

        $this->session->flash('relationship_success', sprintf(
            'Relationship rule added: %s -> %s (%s).',
            $fromType->name(),
            $toType->name(),
            $relationType
        ));

        return Response::redirect('/admin/relationships');
    }

    public function destroyRule(Request $request): Response
    {
        if (!$this->isDeleteMethod($request) || !$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 400);
        }

        $fromTypeName = $request->attribute('fromType');
        $toTypeName = $request->attribute('toType');
        $relationType = $request->attribute('relationType');

        if (!is_string($fromTypeName) || !is_string($toTypeName) || !is_string($relationType)) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $fromType = $this->contentTypes->findByName($fromTypeName);
        $toType = $this->contentTypes->findByName($toTypeName);

        if ($fromType === null || $toType === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $this->relationships->removeRelationshipRule($fromType, $toType, $relationType);
        $this->session->flash('relationship_success', 'Relationship rule deleted.');

        return Response::redirect('/admin/relationships');
    }

    private function queryInt(Request $request, string $key): ?int
    {
        $value = $request->queryParams()[$key] ?? null;

        if (!is_string($value) || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    private function isDeleteMethod(Request $request): bool
    {
        $method = strtoupper($request->method());

        if ($method === 'DELETE') {
            return true;
        }

        if ($method !== 'POST') {
            return false;
        }

        $override = $request->postParams()['_method'] ?? null;

        return is_string($override) && strtoupper($override) === 'DELETE';
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        $token = $request->postParams()['_csrf_token'] ?? null;
        $csrfToken = $request->attribute('csrf_token');

        return is_string($token) && is_string($csrfToken) && hash_equals($csrfToken, $token);
    }
}
