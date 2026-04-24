<?php

namespace App\Console\Commands;

use App\Models\Onu;
use App\Services\GenieAcsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOnuFromGenieAcs extends Command
{
    protected $signature = 'onu:sync-genieacs
                            {--dry-run : Show what would be updated without saving}
                            {--verbose-log : Write per-ONU results to log}';

    protected $description = 'Sync ONU status, RX power, temperature, WAN IP, firmware version from GenieACS VirtualParameters';

    /**
     * If last_inform is within this many hours → ONU is confirmed online via TR-069.
     */
    const ONLINE_THRESHOLD_HOURS = 6;

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $verbose = $this->option('verbose-log');

        $genieacs = new GenieAcsService();

        if (!$genieacs->isAvailable()) {
            $this->error('GenieACS NBI tidak tersedia — sync dibatalkan.');
            return 1;
        }

        $this->info('Mengambil data dari GenieACS...');
        $devices = $genieacs->getDevicesForSync();

        if (empty($devices)) {
            $this->warn('Tidak ada device dari GenieACS.');
            return 0;
        }

        $this->info('Total device di GenieACS: ' . count($devices));

        // Build lookup: serialHex (upper) → device, juga deviceId suffix → device
        $byHex = [];
        foreach ($devices as $id => $d) {
            // serial_hex dari VirtualParameters.getSerialNumber (sudah hex)
            if (!empty($d['serial_hex'])) {
                $byHex[strtoupper($d['serial_hex'])] = $d;
            }
            // Fallback: ambil bagian ketiga dari device ID (OUI-ProductClass-Serial)
            $parts = explode('-', $id, 3);
            if (isset($parts[2])) {
                $byHex[strtoupper($parts[2])] = $d;
            }
        }

        $onus = Onu::whereNull('deleted_at')->get();
        $this->info('Total ONU di DB: ' . $onus->count());

        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar($onus->count());
        $bar->start();

        foreach ($onus as $onu) {
            $bar->advance();

            $device = $this->matchDevice($onu->serial_number, $byHex);

            if (!$device) {
                $notFound++;
                continue;
            }

            $updates = $this->buildUpdates($onu, $device);

            if (empty($updates)) {
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $onu->update($updates);
            }

            if ($verbose) {
                $fields = implode(', ', array_map(
                    fn($k, $v) => "$k=" . (is_null($v) ? 'null' : $v),
                    array_keys($updates),
                    array_values($updates)
                ));
                Log::info("GenieACS sync ONU [{$onu->serial_number}]: {$fields}");
            }

            $updated++;
        }

        $bar->finish();
        $this->newLine();

        $this->info("Selesai." . ($dryRun ? ' [DRY RUN]' : ''));
        $this->line("  Updated : {$updated}");
        $this->line("  No change: {$skipped}");
        $this->line("  Not in GenieACS: {$notFound}");

        Log::info("SyncOnuFromGenieAcs: updated={$updated}, skipped={$skipped}, not_found={$notFound}" . ($dryRun ? ' (dry-run)' : ''));

        return 0;
    }

    /**
     * Match ONU serial number to a GenieACS device entry.
     * Tries:
     *   1. Direct upper-case match of serial_number (e.g. GGCL296294D9)
     *   2. Hex-encoded form (e.g. HWTC → 48575443, concat serial hex)
     */
    protected function matchDevice(string $serialNumber, array $byHex): ?array
    {
        $sn = strtoupper($serialNumber);

        // Try direct match (e.g. GGCL296294D9, FHTTxxxxxx stored as-is in GenieACS ID)
        if (isset($byHex[$sn])) {
            return $byHex[$sn];
        }

        // Try hex-encoded vendor prefix (Huawei: HWTC → 48575443)
        $hexSn = $this->shortSnToHex($sn);
        if ($hexSn && isset($byHex[$hexSn])) {
            return $byHex[$hexSn];
        }

        return null;
    }

    /**
     * Build DB update array from GenieACS device data.
     * Only includes fields that have a real value AND differ from current ONU value.
     */
    protected function buildUpdates(Onu $onu, array $device): array
    {
        $updates = [];

        // --- RX Power ---
        if ($device['rx_power'] !== null) {
            if ((float) ($onu->rx_power ?? PHP_INT_MAX) !== $device['rx_power']) {
                $updates['rx_power'] = $device['rx_power'];
            }
        }

        // --- Temperature ---
        if ($device['temperature'] !== null) {
            if ((float) ($onu->temperature ?? PHP_INT_MAX) !== $device['temperature']) {
                $updates['temperature'] = $device['temperature'];
            }
        }

        // --- WAN IP ---
        if ($device['wan_ip']) {
            if ($onu->wan_ip !== $device['wan_ip']) {
                $updates['wan_ip'] = $device['wan_ip'];
            }
        }

        // --- Software Version ---
        if ($device['software_version']) {
            if ($onu->software_version !== $device['software_version']) {
                $updates['software_version'] = $device['software_version'];
            }
        }

        // --- Hardware Version ---
        if ($device['hardware_version']) {
            if ($onu->hardware_version !== $device['hardware_version']) {
                $updates['hardware_version'] = $device['hardware_version'];
            }
        }

        // --- Vendor (from manufacturer) ---
        if ($device['manufacturer'] && !$onu->vendor) {
            $vendor = $this->normalizeVendor($device['manufacturer']);
            if ($vendor) {
                $updates['vendor'] = $vendor;
            }
        }

        // --- ONU Type (from model) ---
        if ($device['model'] && !$onu->onu_type) {
            $updates['onu_type'] = $device['model'];
        }

        // --- Status from last_inform ---
        if ($device['last_inform']) {
            $lastInform = Carbon::parse($device['last_inform']);
            $hoursAgo   = $lastInform->diffInHours(now());

            if ($hoursAgo <= self::ONLINE_THRESHOLD_HOURS) {
                // Device recently informed → mark online (only if currently unknown)
                if ($onu->status === 'unknown') {
                    $updates['status'] = 'online';
                    $updates['last_online_at'] = $lastInform;
                }
            }
        }

        return $updates;
    }

    /**
     * Normalize manufacturer string to short vendor code stored in ONU.vendor.
     */
    protected function normalizeVendor(string $manufacturer): string
    {
        $m = strtolower($manufacturer);
        if (str_contains($m, 'huawei'))    return 'HWTC';
        if (str_contains($m, 'zte'))       return 'ZTEG';
        if (str_contains($m, 'fiberhome')) return 'FHTT';
        if (str_contains($m, 'nokia'))     return 'ALCL';
        if (str_contains($m, 'tp-link'))   return 'TPLN';
        if (str_contains($m, 'mikrotik'))  return 'MIKR';
        if (str_contains($m, 'zioncom'))   return 'ZION';
        if (str_contains($m, 'raisecom')) return 'GGCL';
        return strtoupper(substr($manufacturer, 0, 4));
    }

    /**
     * Convert short SN (e.g. HWTC6ED42F9A) → hex (48575443 + 6ED42F9A).
     * Mirrors GenieAcsService::shortSnToHex().
     */
    protected function shortSnToHex(string $sn): ?string
    {
        if (strlen($sn) < 8) {
            return null;
        }
        $vendor = substr($sn, 0, 4);
        $serial = substr($sn, 4);
        $vendorHex = '';
        for ($i = 0; $i < strlen($vendor); $i++) {
            $vendorHex .= strtoupper(dechex(ord($vendor[$i])));
        }
        return $vendorHex . strtoupper($serial);
    }
}
