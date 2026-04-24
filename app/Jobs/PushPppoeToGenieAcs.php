<?php

namespace App\Jobs;

use App\Models\Onu;
use App\Services\GenieAcsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushPppoeToGenieAcs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 288 times × 5 menit = 24 jam menunggu ONU online.
     */
    public int $tries = 288;

    /**
     * Job expired after 24 hours regardless of attempts.
     */
    public int $timeout = 60;

    /**
     * Release back to queue after this many seconds if device not yet in GenieACS.
     */
    private const RETRY_DELAY_SECONDS = 300; // 5 menit

    public function __construct(
        public readonly string $onuId,
        public readonly string $pppoeUsername,
        public readonly string $pppoePassword,
        public readonly int    $vlan,
    ) {}

    public function handle(): void
    {
        $onu = Onu::find($this->onuId);
        if (!$onu) {
            Log::info("PushPppoeToGenieAcs: ONU {$this->onuId} not found in DB, skipping.");
            return;
        }

        // If PPPoE username changed since job was dispatched — abort, stale job
        if ($onu->pppoe_username && $onu->pppoe_username !== $this->pppoeUsername) {
            Log::info("PushPppoeToGenieAcs: ONU {$onu->serial_number} PPPoE username changed, job is stale, aborting.");
            return;
        }

        // If WAN IP already assigned — already configured, nothing to do
        if (!empty($onu->wan_ip)) {
            Log::info("PushPppoeToGenieAcs: ONU {$onu->serial_number} already has WAN IP {$onu->wan_ip}, skipping.");
            return;
        }

        $genieacs = new GenieAcsService();
        $device = $genieacs->findDeviceBySerial($onu->serial_number);

        if (!$device) {
            // ONU not yet in GenieACS — release back to queue to retry later
            Log::info("PushPppoeToGenieAcs: ONU {$onu->serial_number} not yet in GenieACS, retrying in " . self::RETRY_DELAY_SECONDS . "s (attempt {$this->attempts()}/{$this->tries}).");
            $this->release(self::RETRY_DELAY_SECONDS);
            return;
        }

        $result = $genieacs->configureWanPppoe($device['device_id'], [
            'username' => $this->pppoeUsername,
            'password' => $this->pppoePassword,
            'vlan'     => $this->vlan,
        ]);

        if ($result['success']) {
            Log::info("PushPppoeToGenieAcs: ONU {$onu->serial_number} PPPoE configured successfully via GenieACS.");
        } elseif ($result['pending'] ?? false) {
            // GenieACS queued the task — it will run at next inform, done.
            Log::info("PushPppoeToGenieAcs: ONU {$onu->serial_number} PPPoE task queued in GenieACS (pending).");
        } else {
            Log::warning("PushPppoeToGenieAcs: ONU {$onu->serial_number} configureWanPppoe failed: " . ($result['message'] ?? 'unknown'));
            // Retry — transient error
            $this->release(self::RETRY_DELAY_SECONDS);
        }
    }

    /**
     * When all retries exhausted — log for investigation.
     */
    public function failed(\Throwable $e): void
    {
        Log::error("PushPppoeToGenieAcs: ONU {$this->onuId} all retries exhausted. Error: " . $e->getMessage());
    }
}
