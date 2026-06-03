<?php

namespace App\Notifications;

use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestSubmittedNotification extends Notification
{
    use Queueable;

    protected $documentRequest;

    public function __construct($documentRequest)
    {
        $this->documentRequest = $documentRequest;
    }

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_document_request');
    }

    public function toDatabase(object $notifiable): DatabaseMessage|array
    {
        return [
            'title' => 'Document Request Submitted',
            'message' => 'A patient requested a dental certificate.',
            'document_request_id' => $this->documentRequest->id ?? null,
            'patient_id' => $this->documentRequest->patient_id ?? null,
            'type' => 'document_request_submitted',
            'url' => url('/dentist/document-requests'),
            'icon' => 'fa-file-lines',
            'event' => 'document.request.submitted',
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
}