<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestHiosoSnmp extends Command
{
    protected $signature = 'test:hioso-snmp {ip=172.16.16.4} {community=public}';
    protected $description = 'Test SNMP on Hioso OLT';

    public function handle()
    {
        $ip = $this->argument('ip');
        $community = $this->argument('community');

        $this->info("=== Testing OLT $ip ===\n");

        snmp_set_quick_print(true);
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

        // System Info
        $this->info("=== System MIB ===");
        $sysOids = [
            'sysDescr' => '1.3.6.1.2.1.1.1.0',
            'sysObjectID' => '1.3.6.1.2.1.1.2.0',
            'sysName' => '1.3.6.1.2.1.1.5.0',
        ];

        foreach ($sysOids as $name => $oid) {
            $val = @snmpget($ip, $community, $oid, 3000000, 1);
            $this->line("$name: " . ($val !== false ? $val : 'N/A'));
        }

        // Walk Enterprise 25355
        $this->info("\n=== Walk Enterprise 25355 ===");
        $result = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.25355', 5000000, 2);
        if ($result && count($result) > 0) {
            $this->line("Total entries: " . count($result));
            $i = 0;
            foreach ($result as $o => $v) {
                $shortOid = str_replace('iso.3.6.1.4.1.25355', '.25355', $o);
                $shortVal = is_string($v) && strlen($v) > 60 ? substr($v, 0, 60) . '...' : $v;
                $this->line("$shortOid = $shortVal");
                if (++$i >= 100) {
                    $this->line("... (truncated)");
                    break;
                }
            }
        } else {
            $this->warn("Empty or failed");
        }

        // Try interfaces
        $this->info("\n=== Interfaces (ifDescr) ===");
        $result = @snmpwalkoid($ip, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 2);
        if ($result && count($result) > 0) {
            foreach ($result as $o => $v) {
                preg_match('/\.(\d+)$/', $o, $m);
                $idx = $m[1] ?? '?';
                $this->line("[$idx] $v");
            }
        } else {
            $this->warn("No interfaces found");
        }

        return Command::SUCCESS;
    }
}
