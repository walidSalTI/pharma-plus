<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Pharmacist;
use App\Models\Pharmacy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Pharmacy $pharmacy,
        public Pharmacist $inviter,
        public array $permissions,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'staff_invitation',
            'pharmacy_id' => $this->pharmacy->id,
            'pharmacy_name' => $this->pharmacy->name,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => ($this->inviter->user?->f_name ?? '').' '.($this->inviter->user?->l_name ?? ''),
            'permissions' => $this->permissions,
        ];
    }
}
