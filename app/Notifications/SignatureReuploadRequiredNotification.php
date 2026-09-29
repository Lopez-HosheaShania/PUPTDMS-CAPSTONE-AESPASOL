<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SignatureReuploadRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Patient $patient,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Signature Re-upload Required',
            'message' => 'Your uploaded signature was marked invalid during manual review. Please upload a valid signature to continue using your record normally.',
            'reason' => $this->reason,
            'url' => route('patient.signature-review.show'),
            'icon' => 'fa-file-signature',
            'patient_id' => $this->patient->id,
            'event' => 'signature.reupload_required',
            'recipient_role' => optional($notifiable->role)->slug,
        ];
    }

}
