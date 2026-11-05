<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplySystemModes;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_is_disabled_and_legacy_setting_cannot_enable_it(): void
    {
        $this->assertFalse(config('app.debug'));
        SystemSetting::setSetting('debug_mode', '1');

        (new ApplySystemModes)->handle(Request::create('/login'), function () {
            $this->assertFalse(config('app.debug'));

            return response('OK');
        });
    }

    public function test_unexpected_json_errors_are_generic_and_reported_internally(): void
    {
        Log::spy();
        Route::get('/_test/unexpected-error', function () {
            throw new \RuntimeException('SQLSTATE secret database password C:\\private\\database.php');
        });

        $response = $this->getJson('/_test/unexpected-error');

        $response->assertStatus(500)->assertExactJson([
            'message' => 'Something went wrong. Please try again later.',
        ]);
        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) =>
            str_contains($message, 'SQLSTATE secret') && isset($context['exception'])
        )->once();
    }

    public function test_html_error_page_does_not_expose_exception_details(): void
    {
        $this->withoutVite();
        Route::get('/_test/html-error', function () {
            throw new \RuntimeException('SQLSTATE secret database C:\\private\\database.php');
        });

        $this->get('/_test/html-error')->assertStatus(500)
            ->assertSee('Something Went Wrong')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('database.php')
            ->assertDontSee('RuntimeException');
    }

    public function test_http_server_error_message_is_not_exposed(): void
    {
        Route::get('/_test/http-error', fn () => abort(503, 'Database host internal-db failed'));

        $this->getJson('/_test/http-error')->assertStatus(503)->assertExactJson([
            'message' => 'The system is temporarily unavailable. Please try again later.',
        ]);
    }

    public function test_development_routes_are_absent_even_in_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        foreach (['/debug-ogos-config', '/test-student-api', '/dev/error-pages/500'] as $url) {
            $this->getJson($url)->assertNotFound();
        }
    }
}
