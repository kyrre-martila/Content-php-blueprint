<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Exception\InvalidContentTypeException;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\ContentRelationshipRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplatePathMap;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;
use DateTimeImmutable;
use RuntimeException;

final class ContentTypeAdminController
{
    /** @var list<string> */
    private const PROTECTED_CONTENT_TYPES = ['page'];

    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly CategoryGroupRepositoryInterface $categoryGroups,
        private readonly ContentRelationshipRepositoryInterface $relationships,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
        private readonly TemplateResolver $templateResolver,
        private readonly TemplatePathMap $templatePathMap,
    ) {
    }

    public function index(Request $request): Response
    {
        $types = $this->contentTypes->findAll();
        $rows = array_map(fn (ContentType $type): array => $this->buildRow($type), $types);

        $html = $this->templateRenderer->renderTemplate('admin/content-types/index.php', [
            'request' => $request,
            'authUser' => $this->authSession->user(),
            'rows' => $rows,
            'success' => $this->session->pullFlash('content_type_success'),
            'error' => $this->session->pullFlash('content_type_error'),
        ]);

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        return $this->renderForm($request, 'admin/content-types/create.php', [
            'errors' => [],
            'old' => [
                'name' => '',
                'slug' => '',
                'view_type' => ContentViewType::SINGLE->value,
                'allowed_category_group_ids' => [],
                'fields' => [],
            ],
        ]);
    }

    public function store(Request $request): Response
    {
        $input = $this->extractInput($request->postParams());
        $errors = $this->validateInput($input, true);

        if ($errors !== []) {
            return $this->renderForm($request, 'admin/content-types/create.php', [
                'errors' => $errors,
                'old' => $input,
            ], 422);
        }

        $contentType = $this->buildContentType($input);

        if ($contentType === null) {
            return $this->renderForm($request, 'admin/content-types/create.php', [
                'errors' => ['general' => 'Unable to save content type. Please verify the entered values.'],
                'old' => $input,
            ], 422);
        }

        $savedType = $this->contentTypes->save($contentType);
        $this->syncAllowedCategoryGroups($savedType, $input['allowed_category_group_ids']);
        $this->session->flash('content_type_success', sprintf('Content type "%s" created.', $savedType->label()));

        return Response::redirect('/admin/content-types');
    }

    public function edit(Request $request): Response
    {
        $identifier = $this->resolveIdentifier($request);

        if ($identifier === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $contentType = $this->contentTypes->findByName($identifier);

        if ($contentType === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        return $this->renderForm($request, 'admin/content-types/edit.php', [
            'contentType' => $contentType,
            'errors' => [],
            'old' => [
                'name' => $contentType->label(),
                'slug' => $contentType->name(),
                'view_type' => $contentType->viewType()->value,
                'allowed_category_group_ids' => $contentType->allowedCategoryGroupIds(),
                'fields' => $this->fieldsForForm($contentType->fields()),
            ],
        ]);
    }

    public function update(Request $request): Response
    {
        $identifier = $this->resolveIdentifier($request);

        if ($identifier === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $existing = $this->contentTypes->findByName($identifier);

        if ($existing === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $input = $this->extractInput($request->postParams());
        $errors = $this->validateInput($input, false, $existing);

        if ($errors !== []) {
            return $this->renderForm($request, 'admin/content-types/edit.php', [
                'contentType' => $existing,
                'errors' => $errors,
                'old' => $input,
            ], 422);
        }

        $contentType = $this->buildContentType($input);

        if ($contentType === null) {
            return $this->renderForm($request, 'admin/content-types/edit.php', [
                'contentType' => $existing,
                'errors' => ['general' => 'Unable to update content type. Please verify the entered values.'],
                'old' => $input,
            ], 422);
        }

        $savedType = $this->contentTypes->save($contentType);
        $this->syncAllowedCategoryGroups($savedType, $input['allowed_category_group_ids']);
        $this->session->flash('content_type_success', sprintf('Content type "%s" updated.', $savedType->label()));

        return Response::redirect('/admin/content-types');
    }

    public function destroy(Request $request): Response
    {
        if (!$this->isDeleteMethod($request) || !$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 400);
        }

        $slug = $request->attribute('slug');

        if (!is_string($slug) || $slug === '') {
            return Response::json(['success' => false], 404);
        }

        $contentType = $this->contentTypes->findByName($slug);

        if ($contentType === null) {
            return Response::json(['success' => false], 404);
        }

        if (!$this->canDelete($contentType)) {
            $this->session->flash('content_type_error', 'This content type is protected and cannot be deleted.');

            return Response::redirect('/admin/content-types');
        }

        try {
            $this->contentTypes->remove($contentType);
        } catch (RuntimeException) {
            $this->session->flash('content_type_error', 'Unable to delete content type. It may still have content items.');

            return Response::redirect('/admin/content-types');
        }

        $this->session->flash('content_type_success', sprintf('Content type "%s" deleted.', $contentType->label()));

        return Response::redirect('/admin/content-types');
    }

    /** @return array<string, mixed> */
    private function buildRow(ContentType $type): array
    {
        $templatePath = $type->isCollectionView()
            ? $this->templatePathMap->collectionTemplate($type)
            : $this->templatePathMap->contentTemplate($type);

        return [
            'name' => $type->label(),
            'slug' => $type->name(),
            'viewType' => $type->viewType()->value,
            'template' => preg_replace('#^templates/#', '', $templatePath) ?? $templatePath,
            'templateExists' => $this->templateResolver->templateExists($templatePath),
            'canDelete' => $this->canDelete($type),
            'editPath' => '/admin/content-types/' . rawurlencode($type->name()) . '/edit',
            'deletePath' => '/admin/content-types/' . rawurlencode($type->name()),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{name: string, slug: string, view_type: string, allowed_category_group_ids: list<int>, fields: list<array{name: string, label: string, field_type: string, is_required: bool, default_value: ?string, settings_json: ?string}>}
     */
    private function extractInput(array $post): array
    {
        $name = is_string($post['name'] ?? null) ? trim($post['name']) : '';
        $slug = is_string($post['slug'] ?? null) ? trim($post['slug']) : '';
        $viewType = is_string($post['view_type'] ?? null) ? trim($post['view_type']) : '';

        $allowedIds = [];
        $rawAllowedIds = $post['allowed_category_group_ids'] ?? [];

        if (is_array($rawAllowedIds)) {
            foreach ($rawAllowedIds as $rawAllowedId) {
                if (is_string($rawAllowedId) && ctype_digit($rawAllowedId)) {
                    $allowedIds[] = (int) $rawAllowedId;
                }
            }
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'view_type' => $viewType,
            'allowed_category_group_ids' => array_values(array_unique($allowedIds)),
            'fields' => $this->extractFieldRows($post),
        ];
    }

    private function renderForm(Request $request, string $template, array $context, int $status = 200): Response
    {
        $selectedType = ($context['contentType'] ?? null);
        $outgoingRelationshipRules = [];
        $incomingRelationshipRules = [];
        if ($selectedType instanceof ContentType) {
            foreach ($this->relationships->findRelationshipRulesForContentType($selectedType) as $rule) {
                if (($rule['from_type'] ?? '') === $selectedType->name()) {
                    $outgoingRelationshipRules[] = $rule;
                }

                if (($rule['to_type'] ?? '') === $selectedType->name()) {
                    $incomingRelationshipRules[] = $rule;
                }
            }
        }

        $html = $this->templateRenderer->renderTemplate($template, [
            'request' => $request,
            'authUser' => $this->authSession->user(),
            'templateExistsMap' => $this->templateResolver->templateExistsMap(),
            'categoryGroups' => $this->categoryGroups->findAllGroups(),
            'outgoingRelationshipRules' => $outgoingRelationshipRules,
            'incomingRelationshipRules' => $incomingRelationshipRules,
            ...$context,
        ]);

        return Response::html($html, $status);
    }

    /**
     * @param array{name: string, slug: string, view_type: string, allowed_category_group_ids: list<int>, fields: list<array{name: string, label: string, field_type: string, is_required: bool, default_value: ?string, settings_json: ?string}>} $input
     * @return array<string, string>
     */
    private function validateInput(array $input, bool $isCreate, ?ContentType $existing = null): array
    {
        $errors = [];

        if ($input['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($input['slug'] === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $input['slug'])) {
            $errors['slug'] = 'Slug must start with a letter and use lowercase letters, numbers, or underscores.';
        }

        if ($input['view_type'] === '' || !in_array($input['view_type'], [ContentViewType::SINGLE->value, ContentViewType::COLLECTION->value], true)) {
            $errors['view_type'] = 'View type must be single or collection.';
        }

        if (!$isCreate && $existing !== null && $input['slug'] !== $existing->name()) {
            $errors['slug'] = 'Slug cannot be changed after creation.';
        }

        if ($isCreate && $input['slug'] !== '' && $this->contentTypes->findByName($input['slug']) !== null) {
            $errors['slug'] = 'A content type with this slug already exists.';
        }

        $fieldNames = [];

        foreach ($input['fields'] as $index => $field) {
            $name = $field['name'];
            $label = $field['label'];
            $fieldType = $field['field_type'];

            if ($name === '') {
                $errors['fields.' . $index . '.name'] = 'Field name is required.';
            } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                $errors['fields.' . $index . '.name'] = 'Field name must start with a letter and use lowercase letters, numbers, or underscores.';
            } elseif (in_array($name, $fieldNames, true)) {
                $errors['fields.' . $index . '.name'] = sprintf('Field name "%s" is duplicated.', $name);
            }

            $fieldNames[] = $name;

            if (trim($label) === '') {
                $errors['fields.' . $index . '.label'] = 'Field label is required.';
            }

            if (!ContentTypeField::isSupportedFieldType($fieldType)) {
                $errors['fields.' . $index . '.field_type'] = 'Field type is not supported.';
            }

            if ($field['settings_json'] !== null && trim($field['settings_json']) !== '') {
                json_decode($field['settings_json'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors['fields.' . $index . '.settings_json'] = 'Settings JSON must be valid JSON.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param array{name: string, slug: string, view_type: string, allowed_category_group_ids: list<int>, fields: list<array{name: string, label: string, field_type: string, is_required: bool, default_value: ?string, settings_json: ?string}>} $input
     */
    private function buildContentType(array $input): ?ContentType
    {
        try {
            $viewType = ContentViewType::fromString($input['view_type']);

            return new ContentType(
                $input['slug'],
                $input['name'],
                $this->templatePreviewPath($input['slug'], $viewType),
                $this->buildFieldObjects($input['fields']),
                $viewType,
                $input['allowed_category_group_ids']
            );
        } catch (InvalidContentTypeException | RuntimeException) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $post
     * @return list<array{name: string, label: string, field_type: string, is_required: bool, default_value: ?string, settings_json: ?string}>
     */
    private function extractFieldRows(array $post): array
    {
        $rawNames = is_array($post['field_name'] ?? null) ? $post['field_name'] : [];
        $rawLabels = is_array($post['field_label'] ?? null) ? $post['field_label'] : [];
        $rawTypes = is_array($post['field_type'] ?? null) ? $post['field_type'] : [];
        $rawRequired = is_array($post['field_required'] ?? null) ? $post['field_required'] : [];
        $rawDefaults = is_array($post['field_default_value'] ?? null) ? $post['field_default_value'] : [];
        $rawSettings = is_array($post['field_settings_json'] ?? null) ? $post['field_settings_json'] : [];

        $rowCount = max(count($rawNames), count($rawLabels), count($rawTypes), count($rawDefaults), count($rawSettings));
        $fields = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $name = is_string($rawNames[$i] ?? null) ? trim($rawNames[$i]) : '';
            $label = is_string($rawLabels[$i] ?? null) ? trim($rawLabels[$i]) : '';
            $fieldType = is_string($rawTypes[$i] ?? null) ? trim($rawTypes[$i]) : '';
            $defaultValue = is_string($rawDefaults[$i] ?? null) ? trim($rawDefaults[$i]) : null;
            $settingsJson = is_string($rawSettings[$i] ?? null) ? trim($rawSettings[$i]) : null;

            if ($name === '' && $label === '' && $fieldType === '' && ($defaultValue === null || $defaultValue === '') && ($settingsJson === null || $settingsJson === '')) {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'label' => $label,
                'field_type' => $fieldType,
                'is_required' => array_key_exists((string) $i, $rawRequired) || array_key_exists($i, $rawRequired),
                'default_value' => $defaultValue === '' ? null : $defaultValue,
                'settings_json' => $settingsJson === '' ? null : $settingsJson,
            ];
        }

        return $fields;
    }

    /**
     * @param list<array{name: string, label: string, field_type: string, is_required: bool, default_value: ?string, settings_json: ?string}> $fields
     * @return list<ContentTypeField>
     */
    private function buildFieldObjects(array $fields): array
    {
        $now = new DateTimeImmutable();
        $result = [];

        foreach ($fields as $index => $field) {
            $settings = null;
            if ($field['settings_json'] !== null && trim($field['settings_json']) !== '') {
                $decoded = json_decode($field['settings_json'], true);
                if (is_array($decoded)) {
                    $settings = $decoded;
                }
            }

            $result[] = new ContentTypeField(
                id: null,
                contentTypeId: 1,
                name: $field['name'],
                label: $field['label'],
                fieldType: $field['field_type'],
                isRequired: $field['is_required'],
                defaultValue: $field['default_value'],
                settings: $settings,
                sortOrder: $index,
                createdAt: $now,
                updatedAt: $now,
            );
        }

        return $result;
    }

    /**
     * @param list<ContentTypeField> $fields
     * @return list<array{name: string, label: string, field_type: string, is_required: bool, default_value: ?string, settings_json: ?string}>
     */
    private function fieldsForForm(array $fields): array
    {
        return array_map(
            static fn (ContentTypeField $field): array => [
                'name' => $field->name(),
                'label' => $field->label(),
                'field_type' => $field->fieldType(),
                'is_required' => $field->isRequired(),
                'default_value' => $field->defaultValue(),
                'settings_json' => $field->settings() === null ? null : json_encode($field->settings(), JSON_PRETTY_PRINT),
            ],
            $fields
        );
    }

    /** @param list<int> $allowedCategoryGroupIds */
    private function syncAllowedCategoryGroups(ContentType $contentType, array $allowedCategoryGroupIds): void
    {
        $currentAllowedGroups = $this->contentTypes->getAllowedCategoryGroups($contentType);
        $currentIds = [];

        foreach ($currentAllowedGroups as $group) {
            $groupId = $group->id();
            if ($groupId !== null) {
                $currentIds[] = $groupId;
            }
        }

        foreach ($allowedCategoryGroupIds as $allowedCategoryGroupId) {
            if (in_array($allowedCategoryGroupId, $currentIds, true)) {
                continue;
            }

            $group = $this->categoryGroups->findById($allowedCategoryGroupId);
            if ($group !== null) {
                $this->contentTypes->attachCategoryGroup($contentType, $group);
            }
        }

        foreach ($currentAllowedGroups as $currentAllowedGroup) {
            $groupId = $currentAllowedGroup->id();
            if ($groupId === null || in_array($groupId, $allowedCategoryGroupIds, true)) {
                continue;
            }

            $this->contentTypes->detachCategoryGroup($contentType, $currentAllowedGroup);
        }
    }

    private function resolveIdentifier(Request $request): ?string
    {
        $identifier = $request->attribute('id');

        if (!is_string($identifier) || $identifier === '') {
            return null;
        }

        return $identifier;
    }

    private function templatePreviewPath(string $slug, ContentViewType $viewType): string
    {
        return $viewType === ContentViewType::COLLECTION
            ? sprintf('collections/%s.php', $slug)
            : sprintf('content/%s.php', $slug);
    }

    private function canDelete(ContentType $type): bool
    {
        return !$this->isProtectedContentType($type);
    }

    private function isProtectedContentType(ContentType $type): bool
    {
        return in_array($type->name(), self::PROTECTED_CONTENT_TYPES, true);
    }

    private function isDeleteMethod(Request $request): bool
    {
        if ($request->method() === 'DELETE') {
            return true;
        }

        $methodOverride = $request->postParams()['_method'] ?? null;

        return is_string($methodOverride) && strtoupper($methodOverride) === 'DELETE';
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        $sessionToken = $this->session->get('_csrf_token');

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $submittedToken = $request->postParams()['_csrf_token'] ?? null;

        return is_string($submittedToken) && hash_equals($sessionToken, $submittedToken);
    }
}
