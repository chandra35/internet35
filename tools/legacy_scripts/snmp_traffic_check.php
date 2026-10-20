<?php
/**
 * Check actual interface traffic values
 */

$host = '172.16.16.4';
$community = 'public';
$timeout = 2000000;
$retries = 1;

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

echo "=== Interface Traffic Details ===\n";
echo "Host: $host\n\n";

// Get interface names
$names = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.2', $timeout, $retries) ?: [];
$status = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.8', $timeout, $retries) ?: [];
$speed = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.5', $timeout, $retries) ?: [];
$inOctets = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.10', $timeout, $retries) ?: [];
$outOctets = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.16', $timeout, $retries) ?: [];
$inErrors = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.14', $timeout, $retries) ?: [];
$outErrors = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.20', $timeout, $retries) ?: [];
$inUcast = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.11', $timeout, $retries) ?: [];
$outUcast = @snmpwalkoid($host, $community, '.1.3.6.1.2.1.2.2.1.17', $timeout, $retries) ?: [];

foreach ($names as $oid => $name) {
    $idx = substr($oid, strrpos($oid, '.') + 1);
    
    $ifStatus = $status[".1.3.6.1.2.1.2.2.1.8.$idx"] ?? null;
    $ifSpeed = $speed[".1.3.6.1.2.1.2.2.1.5.$idx"] ?? 0;
    $ifInOctets = $inOctets[".1.3.6.1.2.1.2.2.1.10.$idx"] ?? 0;
    $ifOutOctets = $outOctets[".1.3.6.1.2.1.2.2.1.16.$idx"] ?? 0;
    $ifInErrors = $inErrors[".1.3.6.1.2.1.2.2.1.14.$idx"] ?? 0;
    $ifOutErrors = $outErrors[".1.3.6.1.2.1.2.2.1.20.$idx"] ?? 0;
    $ifInUcast = $inUcast[".1.3.6.1.2.1.2.2.1.11.$idx"] ?? 0;
    $ifOutUcast = $outUcast[".1.3.6.1.2.1.2.2.1.17.$idx"] ?? 0;
    
    $statusStr = match((int)$ifStatus) {
        1 => 'UP',
        2 => 'DOWN',
        3 => 'TESTING',
        default => 'UNKNOWN'
    };
    
    $speedMbps = round($ifSpeed / 1000000);
    $inMB = round($ifInOctets / 1024 / 1024, 2);
    $outMB = round($ifOutOctets / 1024 / 1024, 2);
    
    echo "[$idx] $name\n";
    echo "    Status: $statusStr | Speed: {$speedMbps} Mbps\n";
    echo "    Traffic In:  {$inMB} MB ({$ifInOctets} bytes) | Packets: $ifInUcast | Errors: $ifInErrors\n";
    echo "    Traffic Out: {$outMB} MB ({$ifOutOctets} bytes) | Packets: $ifOutUcast | Errors: $ifOutErrors\n";
    echo "\n";
}

echo "=== SNMP Useful Summary ===\n";
echo "SNMP on this Hioso OLT provides:\n";
echo "  ✓ Interface names (PON ports + Uplinks)\n";
echo "  ✓ Interface status (UP/DOWN)\n";
echo "  ✓ Interface speed\n";
echo "  ✓ Traffic counters (bytes in/out per interface)\n";
echo "  ✓ Packet counters\n";
echo "  ✓ Error counters\n";
echo "\n";
echo "NOT available via SNMP:\n";
echo "  ✗ ONU list/info (use Telnet instead)\n";
echo "  ✗ ONU optical power (use Telnet instead)\n";
echo "  ✗ ONU status/MAC (use Telnet instead)\n";
echo "  ✗ Enterprise-specific OIDs (25355.x)\n";
