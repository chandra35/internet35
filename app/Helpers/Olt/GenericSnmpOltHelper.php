<?php

namespace App\Helpers\Olt;

use Exception;

/**
 * Generic helper for brands that already exist in the UI but don't yet have
 * full vendor-specific provisioning support.
 *
 * This keeps monitoring/test-connection flows usable and returns clear
 * "not supported yet" messages for write operations.
 */
class GenericSnmpOltHelper extends BaseOltHelper
{
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array
    {
        $brand = $credentials['brand'] ?? 'other';
        $result = [
            'success' => false,
            'brand' => $brand,
            'model' => null,
            'description' => null,
            'firmware' => null,
            'hardware_version' => null,
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'boards' => [],
            'message' => '',
        ];

        try {
            if (!function_exists('snmpget')) {
                $result['message'] = 'SNMP extension tidak terinstall di PHP.';
                return $result;
            }

            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

            $sysDescr = @snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.1.0', 5000000, 2);
            if ($sysDescr === false) {
                $result['message'] = 'Tidak dapat terhubung via SNMP.';
                return $result;
            }

            $result['success'] = true;
            $result['description'] = $sysDescr;
            $result['model'] = trim((string) $sysDescr);
            $result['message'] = 'Perangkat terdeteksi via SNMP, helper masih mode monitoring generik.';
        } catch (\Throwable $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    public function getPonPorts(): array
    {
        return [];
    }

    public function getPonPortInfo(int $slot, int $port): array
    {
        return [
            'slot' => $slot,
            'port' => $port,
            'status' => 'unknown',
        ];
    }

    public function getAllOnus(): array
    {
        return [];
    }

    public function getOnusByPort(int $slot, int $port): array
    {
        return [];
    }

    public function getOnuInfo(int $slot, int $port, int $onuId): array
    {
        throw new Exception('Helper generik belum mendukung pembacaan detail ONU per perangkat.');
    }

    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array
    {
        return [
            'rx_power' => null,
            'tx_power' => null,
            'olt_rx_power' => null,
            'temperature' => null,
            'voltage' => null,
            'bias_current' => null,
        ];
    }

    public function getOnuBySerial(string $serialNumber): ?array
    {
        return null;
    }

    public function getUnregisteredOnus(): array
    {
        return [];
    }

    public function registerOnu(array $params): array
    {
        return [
            'success' => false,
            'message' => 'Provisioning belum tersedia untuk brand ini. Gunakan helper spesifik vendor.',
        ];
    }

    public function unregisterOnu(int $slot, int $port, int $onuId): array
    {
        return [
            'success' => false,
            'message' => 'Unregister ONU belum tersedia untuk brand ini.',
        ];
    }

    public function rebootOnu(int $slot, int $port, int $onuId): array
    {
        return [
            'success' => false,
            'message' => 'Reboot ONU belum tersedia untuk brand ini.',
        ];
    }

    public function getOnuTraffic(int $slot, int $port, int $onuId): array
    {
        return [
            'in_octets' => 0,
            'out_octets' => 0,
            'in_packets' => 0,
            'out_packets' => 0,
        ];
    }

    public function getProfiles(string $type = 'all'): array
    {
        return $type === 'all'
            ? ['line' => [], 'service' => [], 'traffic' => []]
            : [];
    }

    public function applyServiceToOnu(int $slot, int $port, int $onuId, array $serviceConfig): array
    {
        return [
            'success' => false,
            'message' => 'Apply service belum tersedia untuk brand ini.',
        ];
    }

    public function getUplinkPorts(): array
    {
        return array_values($this->getUplinkTrafficStats());
    }

    public function syncAll(): array
    {
        $this->olt->update([
            'last_sync_at' => now(),
            'last_online_at' => now(),
            'status' => 'active',
        ]);

        return [
            'success' => true,
            'pon_ports_synced' => 0,
            'onus_synced' => 0,
            'signals_recorded' => 0,
            'errors' => [],
            'message' => 'Sinkronisasi generik selesai. Brand ini masih monitoring-only.',
        ];
    }
}
