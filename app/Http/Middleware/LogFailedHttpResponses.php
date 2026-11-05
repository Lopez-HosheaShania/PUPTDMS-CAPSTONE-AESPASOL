<?php

namespace App\Http\Middleware;

use App\Helpers\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogFailedHttpResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($response->getStatusCode() >= 400) {
            AuditLogger::logHttpError($response->getStatusCode());
        }

        return $response;
    }
}
