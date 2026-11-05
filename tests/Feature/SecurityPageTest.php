<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_the_response_nonce_without_inline_handlers_or_authentication_session_ids(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ok'])]);
        config(['app.debug' => false]);
        $response = $this->get('/login')->assertOk();
        $policy = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([^']+)'/", $policy, $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach ($document->getElementsByTagName('script') as $script) {
            $this->assertSame($matches[1], $script->getAttribute('nonce'));
            $this->assertStringNotContainsString('cdn.jsdelivr.net', $script->getAttribute('src'));
        }
        $xpath = new DOMXPath($document);
        $this->assertSame(0, $xpath->query('//*[@onclick or @onerror or @onchange or @onsubmit or @style]')->length);
        $this->assertTrue(Str::isUuid(session('chatbot_storage_id')));
        $this->assertStringNotContainsString(session()->getId(), $response->getContent());
    }
}
