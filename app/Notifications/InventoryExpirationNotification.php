<?php

namespace App\Notifications;

use App\Models\Inventory;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InventoryExpirationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Inventory $inventory,
        private readonly string $state
    ) {}

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_inventory_expiration');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload($notifiable) + [
            'created_at_label' => 'Just now',
            'state' => 'unread',
        ]);
    }

    private function payload(object $notifiable): array
    {
        $isExpired = $this->state === 'expired';
        $isToday = $this->state === 'today';
        $expirationDate = $this->inventory->expiration_date?->format('F d, Y');

        return [
            'title' => $isExpired
                ? 'Inventory Item Expired'
                : ($isToday ? 'Inventory Item Expires Today' : 'Inventory Item Expiring Soon'),
            'message' => $isExpired
                ? "{$this->inventory->name} expired on {$expirationDate}."
                : ($isToday
                    ? "{$this->inventory->name} expires today."
                    : "{$this->inventory->name} expires on {$expirationDate}."),
            'url' => $this->resolveTargetUrl($notifiable),
            'icon' => $isExpired || $isToday ? 'fa-triangle-exclamation' : 'fa-clock',
            'inventory_id' => $this->inventory->id,
            'expiration_date' => $this->inventory->expiration_date?->toDateString(),
            'event' => "inventory.expiration.{$this->state}",
            'dedupe_key' => implode('.', [
                'inventory.expiration',
                $this->state,
                $this->inventory->id,
                $this->inventory->expiration_date?->toDateString(),
            ]),
            'recipient_role' => optional($notifiable->role)->slug,
        ];
    }

    private function resolveTargetUrl(object $notifiable): string
    {
        return in_array(optional($notifiable->role)->slug, ['admin', 'super_admin'], true)
            ? route('admin.inventory')
            : route('dentist.dentist.inventory');
    }
}
