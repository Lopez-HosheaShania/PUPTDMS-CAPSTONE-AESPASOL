<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        return $this->apply($request, $next($request));
    }

    public function apply(Request $request, Response $response): Response
    {
        if (! Vite::cspNonce()) {
            Vite::useCspNonce();
        }

        $nonce = Vite::cspNonce();
        $connect = ["'self'", ...$this->origins(config('security.connect_sources', []), ['https', 'wss'])];
        if (config('security.reverb_origin')) {
            $connect = [...$connect, ...$this->origins([config('security.reverb_origin')], app()->environment('local') ? ['ws', 'wss'] : ['wss'])];
        }
        $images = ["'self'", 'data:', 'blob:', ...$this->origins(config('security.image_sources', []), ['https'])];
        $frames = ["'self'", 'blob:', ...$this->origins(config('security.frame_sources', []), ['https'])];
        foreach ([config('services.oidc.logout_url'), config('services.idp.login_url')] as $url) {
            $logout = parse_url((string) $url);
            if ($logout && isset($logout['scheme'], $logout['host'])) {
                $frames = [...$frames, ...$this->origins([
                    $logout['scheme'].'://'.$logout['host'].(isset($logout['port']) ? ':'.$logout['port'] : ''),
                ], app()->environment('local') ? ['http', 'https'] : ['https'])];
            }
        }

        // The Vite development server is local-only; never allow its origins in hosted environments.
        if (app()->environment('local') && Vite::isRunningHot()) {
            $hot = trim((string) file_get_contents(Vite::hotFile()));
            $origin = $this->origins([$hot], ['http', 'https'])[0] ?? null;
            if ($origin) {
                $connect[] = preg_replace('/^http/', 'ws', $origin);
                $connect[] = $origin;
            }
        }

        $scripts = ["'self'", "'nonce-{$nonce}'"];
        $styles = ["'self'", "'nonce-{$nonce}'"];
        $fonts = ["'self'", 'data:'];
        if (isset($origin) && $origin) {
            $scripts[] = $origin;
            $styles[] = $origin;
            $fonts[] = $origin;
            $images[] = $origin;
        }

        $policy = implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scripts),
            "script-src-attr 'none'",
            'style-src '.implode(' ', $styles),
            "style-src-attr 'none'",
            'img-src '.implode(' ', array_unique($images)),
            'font-src '.implode(' ', $fonts),
            'connect-src '.implode(' ', array_unique($connect)),
            'frame-src '.implode(' ', array_unique($frames)),
            "worker-src 'self' blob:",
            "media-src 'self' blob:",
            "manifest-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            ...($request->isSecure() ? ['upgrade-insecure-requests'] : []),
        ]).';';

        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove') && ! headers_sent()) {
            header_remove('X-Powered-By');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');
        $response->headers->set(
            config('security.csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
            $policy,
        );

        if ($request->isSecure() && config('security.hsts_max_age') > 0) {
            $response->headers->set('Strict-Transport-Security',
                'max-age='.config('security.hsts_max_age').(config('security.hsts_include_subdomains') ? '; includeSubDomains' : '')
            );
        }

        // Redirect HTML is unnecessary. Preserve Location, status and cookies.
        if (in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true)
            && $response->headers->has('Location')) {
            $response->setContent('');
            $response->headers->remove('Content-Length');
        }

        return $response;
    }

    private function origins(array $sources, array $schemes): array
    {
        $origins = [];
        foreach ($sources as $source) {
            $parts = parse_url($source);
            if (! $parts || ! in_array($parts['scheme'] ?? '', $schemes, true)
                || empty($parts['host']) || ! preg_match('/^(?:[a-z\d](?:[a-z\d.-]*[a-z\d])?|\[[a-f\d:]+\])$/iD', $parts['host'])
                || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['query']) || isset($parts['fragment'])
                || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
                continue;
            }
            $origins[] = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        }

        return array_values(array_unique($origins));
    }
}
