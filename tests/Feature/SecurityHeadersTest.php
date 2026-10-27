<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_enforced_policy_matches_the_rendered_nonce_and_changes_per_request(): void
    {
        config(['security.csp_report_only' => false]);
        $middleware = new SecurityHeaders;
        $request = Request::create('https://clinic.test/login');
        $render = fn () => new Response('<script nonce="'.Vite::cspNonce().'">window.ready=true;</script>');
        $first = $middleware->handle($request, $render);
        $nonce = Vite::cspNonce();

        $this->assertStringContainsString('nonce="'.$nonce.'"', $first->getContent());
        $policy = $first->headers->get('Content-Security-Policy');
        foreach (["'nonce-{$nonce}'", "script-src-attr 'none'", "style-src-attr 'none'", "form-action 'self'", "frame-ancestors 'self'"] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }
        $this->assertStringNotContainsString('unsafe-inline', $policy);
        $this->assertStringNotContainsString('unsafe-eval', $policy);
        $this->assertFalse($first->headers->has('Content-Security-Policy-Report-Only'));
        $middleware->handle($request, $render);
        $this->assertNotSame($nonce, Vite::cspNonce());
    }

    public function test_https_gets_hsts_but_http_does_not_force_local_https(): void
    {
        config(['security.hsts_max_age' => 86400, 'security.hsts_include_subdomains' => false]);
        $middleware = new SecurityHeaders;
        $https = $middleware->handle(Request::create('https://clinic.test'), fn () => new Response('ok'));
        $this->assertSame('max-age=86400', $https->headers->get('Strict-Transport-Security'));
        $http = $middleware->handle(Request::create('http://localhost'), fn () => new Response('ok'));
        $this->assertFalse($http->headers->has('Strict-Transport-Security'));
        $this->assertStringNotContainsString('upgrade-insecure-requests', $http->headers->get('Content-Security-Policy'));
    }

    public static function localViteOrigins(): array
    {
        return [
            'localhost' => ['http://localhost:5173', 'ws://localhost:5173'],
            'IPv4' => ['http://127.0.0.1:5173', 'ws://127.0.0.1:5173'],
            'IPv4 fallback port' => ['http://127.0.0.1:5174', 'ws://127.0.0.1:5174'],
        ];
    }

    #[DataProvider('localViteOrigins')]
    public function test_local_vite_assets_and_hmr_are_allowed_without_leaking_dev_origins_to_hosted_environments(string $assetOrigin, string $socketOrigin): void
    {
        $file = tempnam(sys_get_temp_dir(), 'clinic-vite-');
        $original = Vite::hotFile();
        file_put_contents($file, $assetOrigin);
        Vite::useHotFile($file);
        try {
            $this->app['env'] = 'local';
            $middleware = new SecurityHeaders;
            $local = $middleware->handle(Request::create('http://localhost'), fn () => new Response('ok'));
            $policy = $local->headers->get('Content-Security-Policy');
            $this->assertStringContainsString($assetOrigin, $policy);
            $this->assertStringContainsString($socketOrigin, $policy);
            $this->assertStringContainsString("font-src 'self' data: {$assetOrigin}", $policy);
            foreach (['staging', 'production'] as $environment) {
                $this->app['env'] = $environment;
                $hosted = $middleware->handle(Request::create('https://clinic.test'), fn () => new Response('ok'));
                $hostedPolicy = $hosted->headers->get('Content-Security-Policy');
                $this->assertStringNotContainsString($assetOrigin, $hostedPolicy);
                $this->assertStringNotContainsString($socketOrigin, $hostedPolicy);
            }
        } finally {
            $this->app['env'] = 'testing';
            Vite::useHotFile($original);
            unlink($file);
        }
    }

    public function test_redirect_keeps_its_destination_status_and_secure_cookie_with_no_body(): void
    {
        $response = new RedirectResponse('/login', 302);
        $response->headers->setCookie(Cookie::create('session', 'opaque', secure: true, httpOnly: true, sameSite: 'lax'));
        $result = (new SecurityHeaders)->handle(Request::create('https://clinic.test'), fn () => $response);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame('/login', $result->headers->get('Location'));
        $this->assertSame('', $result->getContent());
        $cookie = $result->headers->getCookies()[0];
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_origin_allowlists_reject_wildcards_and_policy_injection(): void
    {
        config([
            'security.connect_sources' => ['wss://events.clinic.test', 'https://api.clinic.test', 'https://*.example.com', "https://bad.test;script-src", "https://bad.test/'unsafe-inline'"],
            'security.reverb_origin' => null,
        ]);
        $response = (new SecurityHeaders)->handle(Request::create('https://clinic.test'), fn () => new Response('ok'));
        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('wss://events.clinic.test', $policy);
        $this->assertStringContainsString('https://api.clinic.test', $policy);
        $this->assertStringNotContainsString('bad.test', $policy);
        $this->assertStringNotContainsString('*.example.com', $policy);
    }

    public function test_error_responses_get_headers_and_report_only_is_an_explicit_option(): void
    {
        config(['security.csp_report_only' => true]);
        $response = new Response('Error reference', 500, ['X-Powered-By' => 'PHP/test']);
        $response = (new SecurityHeaders)->apply(Request::create('https://clinic.test'), $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Error reference', $response->getContent());
        $this->assertFalse($response->headers->has('X-Powered-By'));
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('Content-Security-Policy-Report-Only'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_laravel_csrf_cookie_remains_readable_without_changing_session_cookie_defaults(): void
    {
        $request = Request::create('https://clinic.test');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('_token', 'csrf-value');
        $middleware = new class(app(), app('encrypter')) extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
        {
            public function csrfCookie($request, $config): Cookie
            {
                return $this->newCookie($request, $config);
            }
        };
        $cookie = $middleware->csrfCookie($request, array_replace(config('session'), ['secure' => true]));
        $this->assertSame('XSRF-TOKEN', $cookie->getName());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue(config('session.http_only'));
    }
}
