<?php

namespace App\Console\Commands;

use App\Models\Olt;
use App\Models\Onu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOnuOpticalPower extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'onu:sync-power {--olt= : Specific OLT ID to sync}';

    /**
     * The console command description.
     */
    protected $description = 'Sync ONU optical power (RX/TX) from OLT via SNMP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $oltId = $this->option('olt');
        
        $olts = $oltId 
            ? Olt::where('id', $oltId)->get() 
            : Olt::where('status', 'active')->get();

        if ($olts->isEmpty()) {
            $this->error('No active OLTs found.');
            return 1;
        }

        $this->info("Syncing optical power for " . $olts->count() . " OLT(s)...");

        foreach ($olts as $olt) {
            $this->syncOltOnus($olt);
        }

        $this->info('Done!');
        return 0;
    }

    /**
     * Sync optical power for all ONUs on an OLT
     */
    protected function syncOltOnus(Olt $olt): void
    {
        $this->line("Processing OLT: {$olt->name} ({$olt->ip_address})");

        // Check if VSOL
        if (strtolower($olt->brand) !== 'vsol') {
            $this->warn("  Skipping non-VSOL OLT (brand: {$olt->brand})");
            return;
        }

        $onus = Onu::where('olt_id', $olt->id)->get();
        $this->line("  Found " . $onus->count() . " ONUs");

        if ($onus->isEmpty()) {
            return;
        }

        @snmp_set_quick_print(true);
        @snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

        $ip = $olt->ip_address;
        $community = $olt->snmp_community ?? 'private';
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($onus->count());
        $bar->start();

        foreach ($onus as $onu) {
            $port = $onu->port ?? $onu->pon_port ?? 1;
            $onuId = $onu->onu_id ?? $onu->onu_number ?? 0;

            // Skip invalid ONU IDs
            if ($onuId <= 0) {
                $bar->advance();
                continue;
            }

            // VSOL V1600D OIDs for optical power
            // RX Power: .12.2.1.8.1.7.{ponId}.{onuId}
            // TX Power: .12.2.1.8.1.6.{ponId}.{onuId}
            $rxOid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7.$port.$onuId";
            $txOid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6.$port.$onuId";

            $rxRaw = @snmpget($ip, $community, $rxOid, 1000000, 1);
            $txRaw = @snmpget($ip, $community, $txOid, 1000000, 1);

            $rxPower = $this->parseOpticalPower($rxRaw);
            $txPower = $this->parseOpticalPower($txRaw);

            if ($rxPower !== null || $txPower !== null) {
                $onu->rx_power = $rxPower;
                $onu->tx_power = $txPower;
                $onu->olt_rx_power = $rxPower;
                $onu->save();
                $updated++;
            } else {
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Updated: $updated, Skipped: $errors");
        
        Log::info("SyncOnuOpticalPower: OLT {$olt->name} - Updated $updated ONUs, Skipped $errors");
    }

    /**
     * Parse optical power from VSOL SNMP response
     * Format: "0.02 mW (-17.21 dBm)"
     */
    protected function parseOpticalPower($value): ?float
    {
        if (!$value || $value === false) {
            return null;
        }

        // Extract dBm value from parentheses
        if (preg_match('/\(([+-]?[\d.]+)\s*dBm\)/i', $value, $matches)) {
            return round((float) $matches[1], 2);
        }

        return null;
    }
}
