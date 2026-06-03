<?php

namespace App\Notifications;

use App\Models\SystemSetting;
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
        return SystemSetting::notificationVia('notif_document_approved');
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
            'event' => 'document.request.approved',
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