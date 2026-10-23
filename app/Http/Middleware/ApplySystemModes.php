<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemModes
{
    public function handle(Request $request, Closure $next): Response
    {
        $debugMode = SystemSetting::getSetting('debug_mode', '0') === '1';
        config(['app.debug' => $debugMode]);

        return $next($request);
    }
}
