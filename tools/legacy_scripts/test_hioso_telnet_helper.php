<?php
/**
 * Test HiosoHelper Telnet Fallback
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Simple DB setup for testing
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Mock OLT object
$olt = new stdClass();
$olt->id = 1;
$olt->name = 'Test Hioso OLT';
$olt->ip_address = '172.16.16.4';
$olt->snmp_port = 161;
$olt->snmp_community = 'public';
$olt->telnet_port = 23;
$olt->telnet_username = 'admin';
$olt->telnet_password = 'admin';
$olt->total_pon_ports = 4;

echo "=== Test HiosoHelper Telnet Fallback ===\n";
echo "OLT: {$olt->name} ({$olt->ip_address})\n\n";

// Include the helper
require_once __DIR__ . '/app/Helpers/Olt/BaseOltHelper.php';
require_once __DIR__ . '/app/Helpers/Olt/HiosoHelper.php';

// Create anonymous class that extends HiosoHelper to test protected methods
$helper = new class($olt) extends \App\Helpers\Olt\HiosoHelper {
    public function __construct($olt) {
        $this->olt = $olt;
    }
    
    // Expose protected methods for testing
    public function testTelnetConnect() {
        return $this->telnetConnect();
    }
    
    public function testTelnetDisconnect() {
        return $this->telnetDisconnect();
    }
    
    public function testGetOnuInfoViaTelnet($slot, $port) {
        $this->telnetConnect();
        $result = $this->getOnuInfoViaTelnet($slot, $port);
        $this->telnetDisconnect();
        return $result;
    }
    
    public function testGetAllOnusViaTelnet() {
        return $this->getAllOnusViaTelnet();
    }
};

// Test 1: Get ONU info from port 0/1
echo "=== Test 1: Get ONUs from Port 0/1 ===\n";
try {
    $onus = $helper->testGetOnuInfoViaTelnet(0, 1);
    
    if (empty($onus)) {
        echo "No ONUs found on port 0/1\n";
    } else {
        echo "Found " . count($onus) . " ONUs:\n";
        foreach (array_slice($onus, 0, 5) as $onu) {
            echo "  - {$onu['slot']}/{$onu['port']}:{$onu['onu_id']} ";
            echo "MAC:{$onu['mac_address']} ";
            echo "Status:{$onu['status']} ";
            echo "Name:{$onu['description']}\n";
        }
        if (count($onus) > 5) {
            echo "  ... and " . (count($onus) - 5) . " more\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Get all ONUs from all ports
echo "=== Test 2: Get All ONUs (All Ports) ===\n";
try {
    $allOnus = $helper->testGetAllOnusViaTelnet();
    
    echo "Total ONUs found: " . count($allOnus) . "\n\n";
    
    // Group by port
    $byPort = [];
    foreach ($allOnus as $onu) {
        $key = "{$onu['slot']}/{$onu['port']}";
        if (!isset($byPort[$key])) $byPort[$key] = [];
        $byPort[$key][] = $onu;
    }
    
    foreach ($byPort as $port => $onus) {
        $online = count(array_filter($onus, fn($o) => $o['status'] === 'online'));
        $offline = count($onus) - $online;
        echo "Port $port: " . count($onus) . " ONUs (Online: $online, Offline: $offline)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
