<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\SEO\RobotsGenerator;
use App\Http\Request;
use App\Http\Response;

final class RobotsController
{
    public function __construct(private readonly RobotsGenerator $robotsGenerator)
    {
    }

    public function index(Request $request): Response
    {
        return new Response(
            $this->robotsGenerator->generate(),
            200,
            ['Content-Type' => 'text/plain; charset=utf-8']
        );
    }
}
