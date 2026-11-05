<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemModes
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['app.debug' => false]);

        return $next($request);
    }
}
