<?php

namespace App\Notifications;

use App\Models\DentistTransition;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DentistTransitionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DentistTransition $transition,
        private readonly string $event,
        private readonly ?string $recipientRole = null
    ) {}

    public function via(object $notifiable): array
    {
        $key = match ($this->event) {
            'created' => 'notif_dentist_transition_created',
            'updated' => 'notif_dentist_transition_updated',
            'finalized' => 'notif_dentist_transition_finalized',
            'assignments_updated' => 'notif_dentist_transition_updated',
            'checklist_updated' => 'notif_dentist_transition_updated',
            default => 'notif_dentist_transition_created',
        };

        return SystemSetting::notificationVia($key);
    }

    public function toDatabase(object $notifiable): DatabaseMessage|array
    {
        return [
            'type' => 'dentist_transition_'.$this->event,
            'title' => $this->resolveTitle(),
            'message' => $this->resolveMessage(),
            'url' => $this->resolveUrl($notifiable),
            'icon' => 'fa-users-gear',
            'transition_id' => $this->transition->id,
            'dentist_id' => $this->transition->dentist_id,
            'successor_id' => $this->transition->default_successor_dentist_id,
            'status' => $this->transition->status,
            'event' => $this->event,
            'recipient_role' => $this->recipientRole,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge(
            $this->toArray($notifiable),
            [
                'created_at_label' => 'Just now',
                'state' => 'unread',
            ]
        ));
    }

    private function resolveTitle(): string
    {
        return match ($this->event) {
            'effective' => 'Dentist Coverage Effective',
            'completed' => 'Temporary Dentist Coverage Completed',
            'blocked' => 'Dentist Coverage Requires Attention',
            'created' => 'Dentist Continuity Plan Created',
            'updated' => 'Dentist Continuity Plan Updated',
            'finalized' => 'Dentist Continuity Plan Finalized',
            'assignments_updated' => 'Successor Assignments Updated',
            'checklist_updated' => 'Handover Checklist Updated',
            default => 'Dentist Continuity Update',
        };
    }

    private function resolveMessage(): string
    {
        $dentistName = $this->transition->dentist->name ?? 'A dentist';
        $type = str_replace('_', ' ', ucfirst($this->transition->transition_type));

        return match ($this->event) {
            'effective' => "Care responsibilities for {$dentistName} have transferred under the approved plan.",
            'completed' => "Temporary coverage for {$dentistName} has ended and care responsibility has returned.",
            'blocked' => "The continuity plan for {$dentistName} requires administrator review before it can proceed.",
            'created' => "A continuity plan for {$dentistName} ({$type}) has been created.",
            'updated' => "The continuity plan for {$dentistName} ({$type}) has been updated.",
            'finalized' => "The continuity plan for {$dentistName} ({$type}) has been finalized.",
            'assignments_updated' => "Successor assignments for {$dentistName} ({$type}) have been updated.",
            'checklist_updated' => "The handover checklist for {$dentistName} ({$type}) has been updated.",
            default => "The continuity plan for {$dentistName} ({$type}) has changed.",
        };
    }

    private function resolveUrl(object $notifiable): string
    {
        $role = $this->recipientRole ?? optional($notifiable->role)->slug;

        return match ($role) {
            'admin', 'super_admin' => route('admin.dentist-transitions.show', $this->transition),
            'dentist', 'dentist_role' => route('dentist.dentist.patients'),
            default => route('admin.dentist-transitions.show', $this->transition),
        };
    }
}
