<?php

namespace App\Notifications;

use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentRequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public $documentRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_document_rejected');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document Request Rejected',
            'message' => 'Your document request has been rejected.',
            'reason' => $this->documentRequest->rejection_reason,
            'url' => route('patient.dashboard'),
            'icon' => 'fa-file-circle-xmark',
            'document_request_id' => $this->documentRequest->id,
            'status' => $this->documentRequest->status,
            'event' => 'document.request.rejected',
        ];
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