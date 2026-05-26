<?php

namespace App\Notifications;

use App\Models\Olt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Fired when the unregistered-ONU scanner detects an ONU on an OLT
 * for the FIRST time (i.e. it was not present in the previous scan).
 *
 * Stored via the `database` channel into the `notifications` table.
 * Consumed by the bell-dropdown poller in admin layout.
 */
class NewOnuDetected extends Notification
{
    use Queueable;

    public function __construct(
        public Olt $olt,
        public array $onuData // ['serial_number','slot','port','onu_id'?,'vendor'?,'description'?]
    ) {}

    /**
     * Channel: database only (in-app bell). No e-mail / WA — that's a separate feature.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Payload stored in `notifications.data` (JSON).
     * Keep it small + flat — UI & poll endpoint read it directly.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'olt_id'        => $this->olt->id,
            'olt_name'      => $this->olt->name,
            'olt_brand'     => $this->olt->brand,
            'slot'          => $this->onuData['slot'] ?? null,
            'port'          => $this->onuData['port'] ?? null,
            'onu_id'        => $this->onuData['onu_id'] ?? null,
            'serial_number' => $this->onuData['serial_number'] ?? null,
            'vendor'        => $this->onuData['vendor'] ?? null,
            'description'   => $this->onuData['description'] ?? null,
            'detected_at'   => now()->toIso8601String(),
            'target_url'    => url('admin/olts/' . $this->olt->id) . '#unregistered',
        ];
    }
}
