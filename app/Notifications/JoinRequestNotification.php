<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Pharmacist;
use App\Models\Pharmacy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JoinRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Pharmacy $pharmacy,
        public Pharmacist $requester,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'join_request',
            'pharmacy_id' => $this->pharmacy->id,
            'pharmacy_name' => $this->pharmacy->name,
            'requester_id' => $this->requester->id,
            'requester_name' => ($this->requester->user?->f_name ?? '').' '.($this->requester->user?->l_name ?? ''),
        ];
    }
}
