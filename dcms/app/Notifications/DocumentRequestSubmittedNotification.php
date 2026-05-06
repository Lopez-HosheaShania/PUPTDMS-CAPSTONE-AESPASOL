<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
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
        return ['database', 'broadcast'];
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

        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}