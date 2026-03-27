<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;

final class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private readonly Route $route,
        private readonly array $parameters
    ) {
    }

    public function run(Request $request): Response
    {
        return $this->route->run($request->withAddedAttributes($this->parameters));
    }
}
