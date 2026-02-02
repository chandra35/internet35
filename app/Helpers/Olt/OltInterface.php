<?php

namespace App\Helpers\Olt;

use App\Models\Olt;
use App\Models\Onu;

/**
 * Interface for OLT communication
 * All OLT brand helpers must implement this interface
 */
interface OltInterface
{
    /**
     * Set the OLT instance
     */
    public function setOlt(Olt $olt): self;

    /**
     * Identify OLT - Get brand, model, ports info without needing full OLT model
     * Used for initial setup before saving to database
     * 
     * @param string $ipAddress
     * @param int $snmpPort
     * @param string $snmpCommunity
     * @param array $credentials Optional telnet/ssh credentials
     * @return array Contains: brand, model, pon_ports, uplink_ports, firmware, description, etc.
     */
    public static function identify(string $ipAddress, int $snmpPort, string $snmpCommunity, array $credentials = []): array;

    /**
     * Test connection to OLT
     */
    public function testConnection(): array;

    /**
     * Get OLT system information
     */
    public function getSystemInfo(): array;

    /**
     * Get all PON ports info
     */
    public function getPonPorts(): array;

    /**
     * Get PON port details
     */
    public function getPonPortInfo(int $slot, int $port): array;

    /**
     * Get all ONUs list
     */
    public function getAllOnus(): array;

    /**
     * Get ONUs on specific PON port
     */
    public function getOnusByPort(int $slot, int $port): array;

    /**
     * Get ONU details by position
     */
    public function getOnuInfo(int $slot, int $port, int $onuId): array;

    /**
     * Get ONU optical info (Rx/Tx power, etc)
     */
    public function getOnuOpticalInfo(int $slot, int $port, int $onuId): array;

    /**
     * Get ONU by serial number
     */
    public function getOnuBySerial(string $serialNumber): ?array;

    /**
     * Get unregistered/unconfigured ONUs
     */
    public function getUnregisteredOnus(): array;

    /**
     * Register/authorize an ONU
     */
    public function registerOnu(array $params): array;

    /**
     * Unregister/delete an ONU
     */
    public function unregisterOnu(int $slot, int $port, int $onuId): array;

    /**
     * Reboot an ONU
     */
    public function rebootOnu(int $slot, int $port, int $onuId): array;

    /**
     * Get ONU traffic statistics
     */
    public function getOnuTraffic(int $slot, int $port, int $onuId): array;

    /**
     * Get profiles (line, service, traffic)
     */
    public function getProfiles(string $type = 'all'): array;

    /**
     * Apply service profile to ONU
     */
    public function applyServiceToOnu(int $slot, int $port, int $onuId, array $serviceConfig): array;

    /**
     * Get OLT uplink ports status
     */
    public function getUplinkPorts(): array;

    /**
     * Sync all data from OLT
     */
    public function syncAll(): array;

    /**
     * Check if connection method is available
     */
    public function supportsSnmp(): bool;
    public function supportsTelnet(): bool;
    public function supportsSsh(): bool;
    public function supportsApi(): bool;
}
