<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $role = optional($user->role)->slug;
        $limit = (int) $request->integer('limit', 15);
        $limit = max(1, min($limit, 30));

        $baseQuery = $user
            ->currentRoleNotifications()
            ->where(function ($query) {
                $query->whereNull('data->event')
                    ->orWhere('data->event', '!=', 'inventory.low_stock');
            });

        $unreadCount = (clone $baseQuery)
            ->whereNull('read_at')
            ->count();

        $totalCount = (clone $baseQuery)
            ->count();

        $notifications = (clone $baseQuery)
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn($notification) => $this->formatNotification($notification, (string) $role))
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'read_count' => max($totalCount - $unreadCount, 0),
            'total_count' => $totalCount,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    private function formatNotification(object $notification, string $role): array
    {
        $payload = $notification->data ?? [];
        $title = data_get($payload, 'title')
            ?? data_get($payload, 'subject')
            ?? class_basename($notification->type);
        $message = data_get($payload, 'message')
            ?? data_get($payload, 'body')
            ?? data_get($payload, 'description');
        $event = (string) data_get($payload, 'event');
        $expirationDate = data_get($payload, 'expiration_date');

        if (str_starts_with($event, 'inventory.expiration.') && $expirationDate) {
            $date = Carbon::parse($expirationDate);
            $message = str_replace($date->format('M d, Y'), $date->format('F d, Y'), (string) $message);
        }

        $isDentistContinuityNotification = str_ends_with(
            (string) $notification->type,
            'DentistTransitionNotification'
        );

        $url = $isDentistContinuityNotification
            ? '#'
            : $this->resolveNotificationUrl($payload, $role);

        return [
            'id' => $notification->id,
            'dedupe_key' => $notification->id,
            'title' => $title ?: 'Notification',
            'message' => $message,
            'url' => $url ?: '#',
            'state' => $notification->read_at ? 'read' : 'unread',
            'created_at' => optional($notification->created_at)->toIso8601String(),
            'created_at_label' => optional($notification->created_at)->diffForHumans(),
            'icon' => data_get($payload, 'icon') ?? 'fa-bell',
            'event' => $event,
            'mark_read_url' => Route::has('notifications.mark-read')
                ? route('notifications.mark-read', ['notificationId' => $notification->id])
                : null,
        ];
    }

    private function resolveNotificationUrl(array $payload, string $activeRole): string
    {
        $fallbackUrl = data_get($payload, 'url') ?? data_get($payload, 'action_url') ?? '#';
        $event = data_get($payload, 'event') ?? data_get($payload, 'type');

        return match ($event) {
            'appointment.booked', 'appointment.rescheduled' => match ($activeRole) {
                'admin', 'super_admin' => Route::has('admin.admin.appointments')
                    ? route('admin.admin.appointments')
                    : $fallbackUrl,
                'dentist' => Route::has('dentist.dentist.appointments')
                    ? route('dentist.dentist.appointments')
                    : $fallbackUrl,
                'patient' => Route::has('patient.appointment.index')
                    ? route('patient.appointment.index')
                    : $fallbackUrl,
                default => $fallbackUrl,
            },

            'appointment.cancelled' => match ($activeRole) {
                'patient' => Route::has('patient.record')
                    ? route('patient.record', ['appointment' => data_get($payload, 'appointment_id')])
                    : $fallbackUrl,
                'admin', 'super_admin' => Route::has('admin.admin.appointments')
                    ? route('admin.admin.appointments')
                    : $fallbackUrl,
                'dentist' => Route::has('dentist.dentist.appointments')
                    ? route('dentist.dentist.appointments')
                    : $fallbackUrl,
                default => $fallbackUrl,
            },

            'document.request.submitted', 'document_request_submitted' => match ($activeRole) {
                'admin', 'super_admin' => Route::has('admin.document-requests.index')
                    ? route('admin.document-requests.index')
                    : $fallbackUrl,
                'dentist' => Route::has('dentist.dentist.documentrequests')
                    ? route('dentist.dentist.documentrequests')
                    : $fallbackUrl,
                default => $fallbackUrl,
            },

            'document.request.approved',
            'document_request_approved',
            'document.request.rejected',
            'document_request_rejected' => match ($activeRole) {
                'patient' => Route::has('patient.record')
                    ? route('patient.record', ['section' => 'document-requests'])
                    : $fallbackUrl,
                default => $fallbackUrl,
            },

            default => $fallbackUrl,
        };
    }
}
