<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

final class NotificationSummary
{
    public static function make(array $payload): string
    {
        $message = trim((string) (
            data_get($payload, 'message')
            ?? data_get($payload, 'body')
            ?? data_get($payload, 'description')
            ?? ''
        ));

        $event = (string) (
            data_get($payload, 'event')
            ?? data_get($payload, 'type')
            ?? ''
        );

        $type = (string) data_get($payload, 'type', '');

        if ($message === '') {
            return '';
        }

        if (str_starts_with($type, 'dentist_transition_')) {
            return self::summarizeTransition($message);
        }

        if (str_starts_with($event, 'inventory.expiration.')) {
            return self::summarizeInventory($payload, $message);
        }

        return match ($event) {
            'appointment.booked' =>
            $message,

            'appointment.cancelled' =>
            self::summarizeCancellation($message),

            'appointment.completed' =>
            str_replace(
                'has been marked as completed',
                'has been completed',
                $message
            ),

            'appointment.rescheduled' =>
            self::summarizeReschedule($message),

            'appointment.confirmed' =>
            str_replace(
                'has been confirmed',
                'is confirmed',
                $message
            ),

            'appointment.follow_up.reminder',
            'appointment.follow_up.today_reminder' =>
            self::summarizeReminder($message),

            'document.request.submitted',
            'document_request_submitted' =>
            $message,

            'document.request.approved',
            'document_request_approved' =>
            'Your document request is approved and ready for pickup.',

            'document.request.rejected',
            'document_request_rejected' =>
            'Your document request was rejected.',

            'dentist_emergency_out' =>
            self::summarizeEmergency($payload, $message),

            'reserved-booking-period.invitation' =>
            self::summarizeReservedBooking($message),

            default => $message,
        };
    }

    private static function summarizeCancellation(
        string $message
    ): string {
        $summary = preg_replace(
            '/^(Your appointment on .+? at .+?) was cancelled by .+$/s',
            '$1 was cancelled.',
            $message
        );

        return $summary ?: $message;
    }

    private static function summarizeReschedule(
        string $message
    ): string {
        $summary = preg_replace(
            '/^Your appointment was rescheduled by .+? to (.+)$/s',
            'Your appointment was moved to $1',
            $message
        );

        return $summary ?: $message;
    }

    private static function summarizeReminder(
        string $message
    ): string {
        $summary = preg_replace(
            '/^Reminder:\s*You have a follow-up appointment (?:tomorrow,\s*|in 2 days,\s*on\s*|on\s*)/i',
            'Your follow-up appointment is scheduled for ',
            $message
        );

        if (!$summary) {
            return $message;
        }

        $summary = preg_replace(
            '/\.\s*Reason:.*$/s',
            '.',
            $summary
        );

        return $summary ?: $message;
    }

    private static function summarizeEmergency(
        array $payload,
        string $message
    ): string {
        $date = self::formatDate(data_get($payload, 'date'));

        if (!$date) {
            return $message;
        }

        return "Your appointment on {$date} may be delayed or rescheduled due to a dentist emergency. Await further updates.";
    }

    private static function summarizeInventory(
        array $payload,
        string $message
    ): string {
        $date = self::formatDate(
            data_get($payload, 'expiration_date')
        );

        if (!$date) {
            return $message;
        }

        return str_replace(
            'expires today.',
            "expires on {$date}.",
            $message
        );
    }

    private static function summarizeTransition(
        string $message
    ): string {
        $summary = str_replace(
            [
                'has been created',
                'has been updated',
                'has been finalized',
                'have been updated',
                'requires administrator review before it can proceed',
            ],
            [
                'was created',
                'was updated',
                'was finalized',
                'were updated',
                'requires administrator review',
            ],
            $message
        );

        return $summary ?: $message;
    }

    private static function summarizeReservedBooking(
        string $message
    ): string {
        $summary = str_replace(
            'Complete your appointment booking before the reserved period reaches capacity.',
            'Book before slots fill up.',
            $message
        );

        return $summary ?: $message;
    }

    private static function formatDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('M d, Y');
        } catch (\Exception) {
            return null;
        }
    }
}
