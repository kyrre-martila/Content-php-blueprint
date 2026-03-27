<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Pattern\PatternRegistry;

final class PatternController
{
    public function __construct(private readonly PatternRegistry $patternRegistry)
    {
    }

    public function index(Request $_request): Response
    {
        $patterns = [];

        foreach ($this->patternRegistry->all() as $metadata) {
            $patterns[] = [
                'key' => $metadata->key(),
                'name' => $metadata->name(),
                'description' => $metadata->description(),
            ];
        }

        return Response::json(['patterns' => $patterns]);
    }
}
