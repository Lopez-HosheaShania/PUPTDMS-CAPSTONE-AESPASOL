<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use App\Support\BrowserDetection;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AuditLogger
{
    private static bool $loggingException = false;

    public static function log($action, $module, $description = null)
    {
        $request = Request::instance();
        $session = $request && $request->hasSession() ? $request->session() : null;

        try {
            $user = Auth::user();
        } catch (Throwable) {
            $user = null;
        }

        $actorName = 'Unknown User';
        $actorRole = $session?->get('role') ?? 'guest';
        $actorIdentifier = null;

        if ($user) {
            $actorName = $user->name ?? 'Unknown User';
            $actorIdentifier = $user->id;
        }

        if ($actorRole === 'patient') {
            $actorName = $session?->get('patient_name') ?? ($user->name ?? 'Unknown Patient');
            $actorIdentifier = $session?->get('patient_id') ?? ($user->id ?? null);
        } elseif ($actorRole === 'dentist') {
            $actorName = $session?->get('dentist_name') ?? ($user->name ?? 'Unknown Dentist');
            $actorIdentifier = $session?->get('dentist_id') ?? ($user->id ?? null);
        } elseif ($actorRole === 'admin' || $actorRole === 'super_admin') {
            $actorName = $session?->get('admin_name') ?? ($user->name ?? 'Unknown Admin');
            $actorIdentifier = $session?->get('admin_id') ?? ($user->id ?? null);
        }

        $deviceDetails = BrowserDetection::deviceDetailsFromRequest(
            $request
        );

        AuditLog::create([
            'actor_id' => $user?->id,
            'actor_name' => $actorName,
            'actor_role' => $actorRole,
            'actor_identifier' => $actorIdentifier,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'browser_name' => $deviceDetails['browser_name'],

            'device_type' => $deviceDetails['device_type'],

            'device_name' => $deviceDetails['device_name'],

            'os_name' => $deviceDetails['os_name'],
        ]);
    }

    public static function logException(
        Throwable $exception,
        string $module = 'system',
        ?int $statusCode = null
    ): void
    {
        if (self::$loggingException) {
            return;
        }

        self::$loggingException = true;

        try {
            $message = trim($exception->getMessage());
            $statusCode ??= $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;
            $descriptionParts = array_filter([
                (string) $statusCode,
                $message !== '' ? $message : 'No exception message provided.',
            ]);

            self::log(
                'error',
                $module,
                self::limitDescription(implode(' | ', $descriptionParts))
            );
        } catch (Throwable $loggingFailure) {
            Log::channel(config('logging.default'))->error('Failed to write exception to audit_logs.', [
                'original_exception' => get_class($exception),
                'logging_exception' => get_class($loggingFailure),
                'logging_message' => $loggingFailure->getMessage(),
            ]);
        } finally {
            self::$loggingException = false;
        }
    }

    public static function logHttpError(int $statusCode, string $module = 'system'): void
    {
        try {
            self::log(
                'error',
                $module,
                "{$statusCode} | Returned error response"
            );
        } catch (Throwable $loggingFailure) {
            Log::channel(config('logging.default'))->error('Failed to write HTTP error to audit_logs.', [
                'status_code' => $statusCode,
                'logging_exception' => get_class($loggingFailure),
                'logging_message' => $loggingFailure->getMessage(),
            ]);
        }
    }

    private static function limitDescription(string $description, int $limit = 65535): string
    {
        if (mb_strlen($description) <= $limit) {
            return $description;
        }

        return mb_substr($description, 0, $limit - 3) . '...';
    }

}
