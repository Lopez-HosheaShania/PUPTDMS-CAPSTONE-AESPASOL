<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\Cursor;

class NotificationController extends Controller
{
    public static function notificationQuery($user)
    {
        return $user->currentRoleNotifications()
            ->where(function ($query) {
                $query->whereNull('data->event')
                    ->orWhere(
                        'data->event',
                        '!=',
                        'inventory.low_stock'
                    );
            });
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $validated = $request->validate([
            'filter' => 'nullable|in:all,unread,read',
            'limit' => 'nullable|integer|min:1|max:30',
            'cursor' => 'nullable|string|max:2048',
        ]);

        $role = (string) optional($user->role)->slug;
        $filter = $validated['filter'] ?? 'all';
        $limit = (int) ($validated['limit'] ?? 5);

        $baseQuery = self::notificationQuery($user);

        $counts = self::notificationCounts($user);

        $query = clone $baseQuery;

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $cursor = null;

        if (!empty($validated['cursor'])) {
            try {
                $cursor = Cursor::fromEncoded($validated['cursor']);

                $validCursor = $cursor !== null
                    && filled($cursor->parameter('created_at'))
                    && filled($cursor->parameter('id'))
                    && $cursor->pointsToNextItems();
            } catch (\Throwable) {
                $validCursor = false;
            }

            abort_unless(
                $validCursor,
                422,
                'Invalid notification cursor.'
            );
        }

        $page = $query
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                $limit,
                ['*'],
                'cursor',
                $cursor
            );

        $notifications = $page->getCollection()
            ->map(
                fn($notification) =>
                self::formatNotification($notification, $role, $user)
            )
            ->values();

        return response()->json([
            'notifications' => $notifications,
            ...$counts,
            'filter' => $filter,
            'has_more' => $page->hasMorePages(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'server_time' => now()->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public static function notificationCounts($user): array
    {
        $query = self::notificationQuery($user);

        $unread = (clone $query)
            ->whereNull('read_at')
            ->count();

        $total = (clone $query)->count();

        return [
            'unread_count' => $unread,
            'read_count' => max(0, $total - $unread),
            'total_count' => $total,
        ];
    }

    public function markAsRead(
        Request $request,
        string $notificationId
    ): RedirectResponse|JsonResponse {
        $user = $request->user();

        abort_unless($user, 403);

        $notification = self::notificationQuery($user)
            ->whereKey($notificationId)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                ...self::notificationCounts($user),
            ]);
        }

        return back();
    }

    public function markAllAsRead(
        Request $request
    ): RedirectResponse|JsonResponse {
        $user = $request->user();

        abort_unless($user, 403);

        self::notificationQuery($user)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                ...self::notificationCounts($user),
            ]);
        }

        return back();
    }

    public static function formatNotification(
        object $notification,
        string $role,
        $user
    ): array {
        $payload = $notification->data ?? [];

        $title = data_get($payload, 'title')
            ?? data_get($payload, 'subject')
            ?? class_basename($notification->type);

        $message = data_get($payload, 'message')
            ?? data_get($payload, 'body')
            ?? data_get($payload, 'description')
            ?? '';

        $reason = trim((string) data_get($payload, 'reason', ''));

        if (
            $reason !== ''
            && !str_contains((string) $message, $reason)
        ) {
            $message .= ' Reason: ' . $reason;
        }

        $event = (string) (
            data_get($payload, 'event')
            ?? data_get($payload, 'type')
            ?? ''
        );

        if (
            in_array($event, [
                'appointment.follow_up.reminder',
                'appointment.follow_up.today_reminder',
            ], true)
        ) {
            $title = 'Follow-up Appointment Reminder';
        }

        $isDentistContinuity = str_ends_with(
            (string) $notification->type,
            'DentistTransitionNotification'
        );

        $url = $isDentistContinuity
            ? self::resolveContinuityUrl($payload, $role, $user)
            : self::resolveNotificationUrl($payload, $role);

        return [
            'id' => $notification->id,
            'dedupe_key' => $notification->id,
            'title' => $title ?: 'Notification',

            'message' => (string) $message,
            'summary' => NotificationSummary::make($payload),

            'url' => self::safeUrl($url),

            'state' => $notification->read_at
                ? 'read'
                : 'unread',

            'created_at' => optional(
                $notification->created_at
            )->toIso8601String(),

            'created_at_label' => optional(
                $notification->created_at
            )->diffForHumans(),

            'appointment_month' => data_get(
                $payload,
                'appointment_month'
            ),

            'event' => $event,

            'mark_read_url' => route(
                'notifications.mark-read',
                [
                    'notificationId' => $notification->id,
                ]
            ),
        ];
    }

    private static function resolveContinuityUrl(
        array $payload,
        string $role,
        $user
    ): string {
        $transitionId = data_get(
            $payload,
            'transition_id'
        );

        if (!$transitionId) {
            return '#';
        }

        if (
            in_array($role, ['admin', 'super_admin'], true)
            && (
                $role === 'super_admin'
                || $user->hasPermission('view_dentist_transitions')
            )
            && Route::has('admin.dentist-transitions.show')
        ) {
            return route(
                'admin.dentist-transitions.show',
                ['transition' => $transitionId]
            );
        }

        if (
            in_array($role, ['dentist', 'dentist_role'], true)
            && $user->hasPermission('view_dentist_transitions')
            && Route::has('dentist.dentist.transitions.show')
        ) {
            return route(
                'dentist.dentist.transitions.show',
                ['transition' => $transitionId]
            );
        }

        return '#';
    }

    private static function safeUrl(?string $url): string
    {
        if (!$url || $url === '#') {
            return '#';
        }

        if (
            str_contains($url, '\\')
            || preg_match('/[\x00-\x20\x7F]/', $url)
        ) {
            return '#';
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return '#';
        }

        $origin = parse_url(request()->getSchemeAndHttpHost());

        if (
            isset($parts['scheme'], $parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && strtolower($parts['scheme'])
            === strtolower($origin['scheme'] ?? '')
            && strtolower($parts['host'])
            === strtolower($origin['host'] ?? '')
            && ($parts['port'] ?? null)
            === ($origin['port'] ?? null)
        ) {
            return $url;
        }

        return '#';
    }

    private static function resolveNotificationUrl(
        array $payload,
        string $activeRole
    ): string {
        $fallbackUrl = data_get($payload, 'url')
            ?? data_get($payload, 'action_url')
            ?? '#';

        $event = data_get($payload, 'event')
            ?? data_get($payload, 'type');

        return match ($event) {
            'appointment.booked',
            'appointment.rescheduled' => match ($activeRole) {
                'admin', 'super_admin' =>
                route('admin.admin.appointments'),

                'dentist', 'dentist_role' =>
                route('dentist.dentist.appointments'),

                'patient', 'patient_role' =>
                route('patient.appointment.index'),

                default => $fallbackUrl,
            },

            'appointment.cancelled',
            'appointment.completed' => match ($activeRole) {
                'patient', 'patient_role' =>
                route('patient.record', [
                    'appointment' => data_get(
                        $payload,
                        'appointment_id'
                    ),
                ]),

                'admin', 'super_admin' =>
                route('admin.admin.appointments'),

                'dentist', 'dentist_role' =>
                route('dentist.dentist.appointments'),

                default => $fallbackUrl,
            },

            'document.request.submitted',
            'document_request_submitted' => match ($activeRole) {
                'admin', 'super_admin' =>
                route('admin.document-requests.index'),

                'dentist', 'dentist_role' =>
                route('dentist.dentist.documentrequests'),

                default => $fallbackUrl,
            },

            'document.request.approved',
            'document_request_approved',
            'document.request.rejected',
            'document_request_rejected' => match ($activeRole) {
                'patient', 'patient_role' =>
                route('patient.record', [
                    'section' => 'document-requests',
                ]),

                default => $fallbackUrl,
            },

            default => $fallbackUrl,
        };
    }
}
