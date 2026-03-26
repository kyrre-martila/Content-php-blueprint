<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;

final class HealthController
{
    public function show(Request $request): Response
    {
        return Response::json(['status' => 'ok']);
    }
}
