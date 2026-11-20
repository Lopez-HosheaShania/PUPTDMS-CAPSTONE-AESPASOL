<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Symfony\Component\HttpFoundation\Cookie;

class HttpOnlyXsrfToken extends ValidateCsrfToken
{
    protected function newCookie($request, $config)
    {
        return new Cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            true,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false,
        );
    }
}
