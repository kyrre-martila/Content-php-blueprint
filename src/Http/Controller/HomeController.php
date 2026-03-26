<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;

final class HomeController
{
    public function index(Request $request): Response
    {
        $body = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Content PHP Blueprint</title>
</head>
<body>
    <h1>Content PHP Blueprint</h1>
    <p>Request handled via Router + Kernel.</p>
    <p><strong>Method:</strong> {$request->method()}</p>
    <p><strong>Path:</strong> {$request->path()}</p>
</body>
</html>
HTML;

        return Response::html($body);
    }
}
