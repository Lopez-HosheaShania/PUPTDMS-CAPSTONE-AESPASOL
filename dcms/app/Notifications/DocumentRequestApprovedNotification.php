<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public $documentRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast']; // IMPORTANT
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document Request Approved',
            'message' => 'Your document request is approved and ready for pick up.',
            'url' => route('patient.document.approved', $this->documentRequest->id),
            'icon' => 'fa-file-circle-check',
            'document_request_id' => $this->documentRequest->id,
            'status' => $this->documentRequest->status,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}