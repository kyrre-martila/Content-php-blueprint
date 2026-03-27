<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\Pattern\PatternRegistry;
use DateTimeImmutable;
use Throwable;

final class EditorModeController
{
    public function __construct(
        private readonly EditorMode $editorMode,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly PatternRegistry $patternRegistry
    ) {
    }

    public function enable(Request $request): Response
    {
        if (!$this->editorMode->canEdit()) {
            return Response::redirect('/admin');
        }

        $this->editorMode->enable();

        return Response::redirect($this->redirectTarget($request));
    }

    public function disable(Request $request): Response
    {
        if (!$this->editorMode->canEdit()) {
            return Response::redirect('/admin');
        }

        $this->editorMode->disable();

        return Response::redirect($this->redirectTarget($request));
    }

    public function update(Request $request): Response
    {
        if (!$this->editorMode->canEdit() || !$this->editorMode->isActive()) {
            return Response::json(['error' => 'editor_mode_inactive_or_unauthorized'], 403);
        }

        $post = $request->postParams();
        $type = $this->stringValue($post['target_type'] ?? null);
        $field = $this->stringValue($post['field'] ?? null);
        $value = $this->stringValue($post['value'] ?? null);

        if ($type === null || $field === null || $value === null) {
            return Response::json(['error' => 'invalid_payload'], 422);
        }

        if ($type === 'content_item') {
            return $this->updateContentItemTitle($post, $field, $value);
        }

        if ($type === 'pattern_block') {
            return $this->updatePatternBlockField($post, $field, $value);
        }

        return Response::json(['error' => 'unsupported_target_type'], 422);
    }

    /**
     * @param array<string, mixed> $post
     */
    private function updateContentItemTitle(array $post, string $field, string $value): Response
    {
        if ($field !== 'title') {
            return Response::json(['error' => 'unsupported_content_item_field'], 422);
        }

        $id = $this->intValue($post['id'] ?? null);

        if ($id === null) {
            return Response::json(['error' => 'invalid_content_item_id'], 422);
        }

        $item = $this->contentItems->findById($id);

        if ($item === null) {
            return Response::json(['error' => 'content_item_not_found'], 404);
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return Response::json(['error' => 'title_cannot_be_empty'], 422);
        }

        try {
            $updated = $item->withTitle($trimmed, new DateTimeImmutable());
            $saved = $this->contentItems->save($updated);
        } catch (Throwable) {
            return Response::json(['error' => 'failed_to_update_content_item'], 500);
        }

        return Response::json([
            'ok' => true,
            'value' => $saved->title(),
        ]);
    }

    /**
     * @param array<string, mixed> $post
     */
    private function updatePatternBlockField(array $post, string $field, string $value): Response
    {
        $contentId = $this->intValue($post['content_id'] ?? null);
        $blockIndex = $this->intValue($post['block_index'] ?? null);

        if ($contentId === null || $blockIndex === null || $blockIndex < 0) {
            return Response::json(['error' => 'invalid_pattern_target'], 422);
        }

        $item = $this->contentItems->findById($contentId);

        if ($item === null) {
            return Response::json(['error' => 'content_item_not_found'], 404);
        }

        $blocks = $item->patternBlocks();

        if (!isset($blocks[$blockIndex])) {
            return Response::json(['error' => 'pattern_block_not_found'], 404);
        }

        $block = $blocks[$blockIndex];
        $patternSlug = $block['pattern'] ?? '';
        $pattern = $this->patternRegistry->get($patternSlug);

        if ($pattern === null) {
            return Response::json(['error' => 'pattern_not_registered'], 422);
        }

        $allowedType = null;

        foreach ($pattern['fields'] as $patternField) {
            if ($patternField['name'] === $field) {
                $allowedType = $patternField['type'];
                break;
            }
        }

        if (!in_array($allowedType, ['text', 'textarea'], true)) {
            return Response::json(['error' => 'unsupported_pattern_field'], 422);
        }

        $blocks[$blockIndex]['data'][$field] = trim($value);

        try {
            $updated = $item->withPatternBlocks($blocks, new DateTimeImmutable());
            $saved = $this->contentItems->save($updated);
        } catch (Throwable) {
            return Response::json(['error' => 'failed_to_update_pattern_block'], 500);
        }

        $savedBlocks = $saved->patternBlocks();

        return Response::json([
            'ok' => true,
            'value' => $savedBlocks[$blockIndex]['data'][$field] ?? '',
        ]);
    }

    private function redirectTarget(Request $request): string
    {
        $referer = $request->serverParams()['HTTP_REFERER'] ?? null;

        if (!is_string($referer) || trim($referer) === '') {
            return '/admin';
        }

        $path = parse_url($referer, PHP_URL_PATH);

        if (!is_string($path) || trim($path) === '') {
            return '/admin';
        }

        $query = parse_url($referer, PHP_URL_QUERY);

        if (!is_string($query) || trim($query) === '') {
            return $path;
        }

        return $path . '?' . $query;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }

    private function intValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value) || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }
}
