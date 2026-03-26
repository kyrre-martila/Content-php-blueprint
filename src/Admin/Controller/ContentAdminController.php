<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\Content\CreateContentItem;
use App\Application\Content\Dto\ContentItemInput;
use App\Application\Content\ListContentItems;
use App\Application\Content\UpdateContentItem;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplateRenderer;

final class ContentAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ListContentItems $listContentItems,
        private readonly CreateContentItem $createContentItem,
        private readonly UpdateContentItem $updateContentItem,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session
    ) {
    }

    public function index(Request $request): Response
    {
        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/content/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'items' => $this->listContentItems->execute(),
                'success' => $this->session->pullFlash('content_success'),
            ]
        );

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/content/create.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentTypes' => $this->contentTypes->findAll(),
                'errors' => [],
                'old' => [
                    'title' => '',
                    'slug' => '',
                    'status' => '',
                    'content_type' => '',
                    'body' => '',
                ],
            ]
        );

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $input = $this->buildInput($request);
        $result = $this->createContentItem->execute($input);

        if (!$result->isValid) {
            return $this->renderCreateWithErrors($request, $result->errors, $input);
        }

        $this->session->flash('content_success', 'Content item created successfully.');

        return Response::redirect('/admin/content');
    }

    public function edit(Request $request): Response
    {
        $id = $this->resolveContentItemId($request);

        if ($id === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $item = $this->contentItems->findById($id);

        if ($item === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/content/edit.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentItem' => $item,
                'contentTypes' => $this->contentTypes->findAll(),
                'errors' => [],
                'old' => [
                    'title' => $item->title(),
                    'slug' => $item->slug()->value(),
                    'status' => $item->status()->value,
                    'content_type' => $item->type()->name(),
                    'body' => '',
                ],
            ]
        );

        return Response::html($html);
    }

    public function update(Request $request): Response
    {
        $id = $this->resolveContentItemId($request);

        if ($id === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $input = $this->buildInput($request);
        $result = $this->updateContentItem->execute($id, $input);

        if (!$result->isValid) {
            return $this->renderEditWithErrors($request, $id, $result->errors, $input);
        }

        $this->session->flash('content_success', 'Content item updated successfully.');

        return Response::redirect('/admin/content');
    }

    private function buildInput(Request $request): ContentItemInput
    {
        $post = $request->postParams();

        return new ContentItemInput(
            is_string($post['title'] ?? null) ? $post['title'] : '',
            is_string($post['slug'] ?? null) ? $post['slug'] : '',
            is_string($post['status'] ?? null) ? $post['status'] : '',
            is_string($post['content_type'] ?? null) ? $post['content_type'] : '',
            is_string($post['body'] ?? null) ? $post['body'] : null
        );
    }

    private function resolveContentItemId(Request $request): ?int
    {
        $id = $request->attribute('id');

        if (!is_string($id) || !ctype_digit($id)) {
            return null;
        }

        return (int) $id;
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderCreateWithErrors(Request $request, array $errors, ContentItemInput $input): Response
    {
        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/content/create.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentTypes' => $this->contentTypes->findAll(),
                'errors' => $errors,
                'old' => [
                    'title' => $input->title,
                    'slug' => $input->slug,
                    'status' => $input->status,
                    'content_type' => $input->contentType,
                    'body' => $input->body ?? '',
                ],
            ]
        );

        return Response::html($html, 422);
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderEditWithErrors(Request $request, int $id, array $errors, ContentItemInput $input): Response
    {
        $item = $this->contentItems->findById($id);

        if ($item === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/content/edit.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentItem' => $item,
                'contentTypes' => $this->contentTypes->findAll(),
                'errors' => $errors,
                'old' => [
                    'title' => $input->title,
                    'slug' => $input->slug,
                    'status' => $input->status,
                    'content_type' => $input->contentType,
                    'body' => $input->body ?? '',
                ],
            ]
        );

        return Response::html($html, 422);
    }
}
