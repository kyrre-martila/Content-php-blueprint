<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\SEO\SitemapGenerator;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Http\Request;
use App\Http\Response;

final class SitemapController
{
    public function __construct(
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly SitemapGenerator $sitemapGenerator
    ) {
    }

    public function index(Request $request): Response
    {
        $xml = $this->sitemapGenerator->generate($this->contentItems->findPublished()['items']);

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
