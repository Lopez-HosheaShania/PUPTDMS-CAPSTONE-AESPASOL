<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

class HttpBootstrapTest extends TestCase
{
    public static function proxyRequests(): array
    {
        return [
            'configured proxy' => ['10.23.0.5', true],
            'untrusted client' => ['203.0.113.23', false],
        ];
    }

    #[DataProvider('proxyRequests')]
    public function test_http_kernel_resolves_and_serves_a_request_before_any_console_bootstrap(string $remoteAddress, bool $secure): void
    {
        $root = dirname(__DIR__, 2);
        $script = <<<'PHP'
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$configWasLoaded = $app->bound('config');
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('http://localhost/up', 'GET', [], [], [], [
    'REMOTE_ADDR' => $argv[2],
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);
$response = $kernel->handle($request);
echo json_encode([
    'config_was_loaded' => $configWasLoaded,
    'status' => $response->getStatusCode(),
    'has_csp' => $response->headers->has('Content-Security-Policy'),
    'has_hsts' => $response->headers->has('Strict-Transport-Security'),
]);
$kernel->terminate($request, $response);
PHP;
        $process = new Process([PHP_BINARY, '-r', $script, $root, $remoteAddress], $root, [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'APP_CONFIG_CACHE' => sys_get_temp_dir().'/clinic-http-config-'.bin2hex(random_bytes(8)).'.php',
            'APP_ROUTES_CACHE' => sys_get_temp_dir().'/clinic-http-routes-'.bin2hex(random_bytes(8)).'.php',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'SESSION_DRIVER' => 'array',
            'CACHE_STORE' => 'array',
            'LOG_CHANNEL' => 'null',
            'BROADCAST_CONNECTION' => 'null',
            'MAIL_MAILER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'CSP_REPORT_ONLY' => 'false',
            'TRUSTED_PROXIES' => '10.23.0.5',
            'HSTS_MAX_AGE' => '86400',
        ]);
        $process->setTimeout(30);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertFalse($result['config_was_loaded']);
        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['has_csp']);
        $this->assertSame($secure, $result['has_hsts']);
    }
}
