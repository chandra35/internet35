<?php

namespace App\Helpers\Olt;

use App\Models\Olt;
use Exception;

/**
 * Factory class to create appropriate OLT helper based on brand
 */
class OltFactory
{
    /**
     * Create OLT helper instance based on OLT brand
     */
    public static function make(Olt $olt): OltInterface
    {
        $helper = match($olt->brand) {
            Olt::BRAND_ZTE => new ZteC320Helper(),
            Olt::BRAND_HIOSO => new HiosoHelper(),
            Olt::BRAND_HSGQ => new HsgqHelper(),
            Olt::BRAND_VSOL => new VsolHelper(),
            Olt::BRAND_HUAWEI => new HuaweiHelper(),
            default => throw new Exception("Unsupported OLT brand: {$olt->brand}"),
        };

        return $helper->setOlt($olt);
    }

    /**
     * Identify OLT by IP address - auto-detect brand and get board info
     * 
     * @param string $ipAddress
     * @param int $snmpPort
     * @param string $snmpCommunity
     * @param array $credentials Optional telnet/ssh credentials
     * @return array
     */
    // Timeout constants (in seconds)
    public const IDENTIFY_TIMEOUT = 30; // Max total time for identify
    public const SNMP_TIMEOUT = 5000000; // 5 seconds in microseconds
    public const CLI_TIMEOUT = 10; // Telnet/SSH socket timeout

    public static function identify(string $ipAddress, int $snmpPort = 161, string $snmpCommunity = 'public', array $credentials = []): array
    {
        $result = [
            'success' => false,
            'brand' => null,
            'brand_label' => null,
            'model' => null,
            'description' => null,
            'firmware' => null,
            'hardware_version' => null,
            'total_pon_ports' => 0,
            'total_uplink_ports' => 0,
            'boards' => [],
            'message' => '',
        ];

        // Set max execution time for this request
        $startTime = microtime(true);
        set_time_limit(self::IDENTIFY_TIMEOUT + 5);

        try {
            // Helper to check timeout
            $checkTimeout = function() use ($startTime) {
                if ((microtime(true) - $startTime) > self::IDENTIFY_TIMEOUT) {
                    throw new Exception('Timeout: Proses identifikasi melebihi ' . self::IDENTIFY_TIMEOUT . ' detik');
                }
            };

            // Check if using Telnet or SSH directly (without SNMP)
            $useTelnet = !empty($credentials['telnet_enabled']);
            $useSsh = !empty($credentials['ssh_enabled']);
            
            // Check if brand is already specified
            $specifiedBrand = $credentials['brand'] ?? null;

            // If brand is specified, ONLY use that helper (don't fallback to others)
            if ($specifiedBrand) {
                $helperClass = match($specifiedBrand) {
                    Olt::BRAND_ZTE, 'zte' => ZteC320Helper::class,
                    Olt::BRAND_VSOL, 'vsol' => VsolHelper::class,
                    Olt::BRAND_HIOSO, 'hioso' => HiosoHelper::class,
                    Olt::BRAND_HSGQ, 'hsgq' => HsgqHelper::class,
                    Olt::BRAND_HUAWEI, 'huawei' => HuaweiHelper::class,
                    default => null,
                };
                
                if ($helperClass) {
                    $result = $helperClass::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
                    // Add brand label
                    if ($result['brand']) {
                        $brands = self::getSupportedBrands();
                        $result['brand_label'] = $brands[$result['brand']]['name'] ?? ucfirst($result['brand']);
                    }
                    // Return directly - don't try other helpers
                    return $result;
                }
                
                $result['message'] = "Brand '$specifiedBrand' tidak dikenal.";
                return $result;
            }

            // If using Telnet/SSH only without specified brand, try auto-detect
            if ($useTelnet || $useSsh) {
                // Try ZTE first (most common)
                $result = ZteC320Helper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
                
                if ($result['success']) {
                    // Add brand label
                    if ($result['brand']) {
                        $brands = self::getSupportedBrands();
                        $result['brand_label'] = $brands[$result['brand']]['name'] ?? ucfirst($result['brand']);
                    }
                    return $result;
                }

                // Try other brands via CLI
                $helpers = [
                    HuaweiHelper::class,
                    HiosoHelper::class,
                    VsolHelper::class,
                    HsgqHelper::class,
                ];

                foreach ($helpers as $helperClass) {
                    $result = $helperClass::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
                    if ($result['success']) {
                        if ($result['brand']) {
                            $brands = self::getSupportedBrands();
                            $result['brand_label'] = $brands[$result['brand']]['name'] ?? ucfirst($result['brand']);
                        }
                        return $result;
                    }
                }

                $result['message'] = 'Tidak dapat mengidentifikasi OLT via ' . ($useTelnet ? 'Telnet' : 'SSH') . '. Periksa credentials atau coba pilih Brand.';
                return $result;
            }

            // Using SNMP - check if extension available
            if (!function_exists('snmpget')) {
                $result['message'] = 'SNMP extension tidak terinstall di PHP. Silakan install php-snmp extension atau gunakan Telnet/SSH.';
                return $result;
            }

            // Try basic SNMP to detect brand
            \snmp_set_quick_print(true);
            \snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            
            $checkTimeout();
            $sysDescr = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.1.0', self::SNMP_TIMEOUT, 2);
            
            if ($sysDescr === false) {
                $result['message'] = 'Tidak dapat terhubung via SNMP. Periksa IP address, port, dan community string. Atau coba gunakan Telnet/SSH.';
                return $result;
            }

            // Also get sysObjectID for Enterprise ID detection
            $checkTimeout();
            $sysObjectId = @\snmpget($ipAddress, $snmpCommunity, '1.3.6.1.2.1.1.2.0', self::SNMP_TIMEOUT, 2);

            $sysDescrLower = strtolower($sysDescr);
            $detectedBrand = null;

            // Enterprise ID mapping
            $enterpriseMap = [
                '3902' => Olt::BRAND_ZTE,      // ZTE
                '2011' => Olt::BRAND_HUAWEI,   // Huawei
                '17409' => Olt::BRAND_HIOSO,   // Hioso
                '37950' => Olt::BRAND_VSOL,    // VSOL
            ];

            // Try to detect from Enterprise ID in sysObjectID (most reliable)
            if ($sysObjectId !== false) {
                // sysObjectID format: 1.3.6.1.4.1.<enterprise_id>.<...> or iso.3.6.1.4.1.<enterprise_id>.<...>
                // Also handles: .1.3.6.1.4.1.<enterprise_id>.<...>
                if (preg_match('/(?:iso|\.?1)\.3\.6\.1\.4\.1\.(\d+)/', $sysObjectId, $matches)) {
                    $enterpriseId = $matches[1];
                    if (isset($enterpriseMap[$enterpriseId])) {
                        $detectedBrand = $enterpriseMap[$enterpriseId];
                    }
                }
            }

            // Fallback: Detect brand from sysDescr string matching
            if (!$detectedBrand) {
                if (strpos($sysDescrLower, 'zte') !== false || strpos($sysDescrLower, 'zxa10') !== false) {
                    $detectedBrand = Olt::BRAND_ZTE;
                } elseif (strpos($sysDescrLower, 'huawei') !== false || strpos($sysDescrLower, 'ma56') !== false) {
                    $detectedBrand = Olt::BRAND_HUAWEI;
                } elseif (strpos($sysDescrLower, 'hioso') !== false || strpos($sysDescrLower, 'ha73') !== false) {
                    $detectedBrand = Olt::BRAND_HIOSO;
                } elseif (strpos($sysDescrLower, 'vsol') !== false || strpos($sysDescrLower, 'v1600') !== false) {
                    $detectedBrand = Olt::BRAND_VSOL;
                } elseif (strpos($sysDescrLower, 'hsgq') !== false) {
                    $detectedBrand = Olt::BRAND_HSGQ;
                }
            }

            // Route to appropriate helper based on detected brand
            if ($detectedBrand === Olt::BRAND_ZTE) {
                $result = ZteC320Helper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
            } elseif ($detectedBrand === Olt::BRAND_HUAWEI) {
                $result = HuaweiHelper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
            } elseif ($detectedBrand === Olt::BRAND_HIOSO) {
                $result = HiosoHelper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
            } elseif ($detectedBrand === Olt::BRAND_VSOL) {
                $result = VsolHelper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
            } elseif ($detectedBrand === Olt::BRAND_HSGQ) {
                $result = HsgqHelper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
            } else {
                // Unknown brand - try ZTE first as default (common in Indonesia)
                $result = ZteC320Helper::identify($ipAddress, $snmpPort, $snmpCommunity, $credentials);
                
                if (!$result['success']) {
                    // Generic result - use original sysDescr (not lowercased)
                    $result['description'] = $sysDescr;
                    $result['message'] = 'Brand tidak dapat diidentifikasi. System Description: ' . $sysDescr . ($sysObjectId ? ' | sysObjectID: ' . $sysObjectId : '');
                }
            }

            // Add brand label
            if ($result['brand']) {
                $brands = self::getSupportedBrands();
                $result['brand_label'] = $brands[$result['brand']]['name'] ?? ucfirst($result['brand']);
            }

        } catch (Exception $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get supported brands list
     */
    public static function getSupportedBrands(): array
    {
        return [
            Olt::BRAND_ZTE => [
                'name' => 'ZTE',
                'models' => ['C320', 'C300', 'C220'],
                'features' => ['snmp', 'telnet', 'ssh', 'provisioning', 'full_control'],
            ],
            Olt::BRAND_HIOSO => [
                'name' => 'Hioso',
                'models' => ['HA7302', 'HA7304', 'HA7308'],
                'features' => ['snmp', 'monitoring'],
            ],
            Olt::BRAND_HSGQ => [
                'name' => 'HSGQ',
                'models' => ['8PON', '16PON'],
                'features' => ['snmp', 'monitoring'],
            ],
            Olt::BRAND_VSOL => [
                'name' => 'VSOL',
                'models' => ['V1600D', 'V1600G'],
                'features' => ['snmp', 'basic_control'],
            ],
            Olt::BRAND_HUAWEI => [
                'name' => 'Huawei',
                'models' => ['MA5608T', 'MA5680T'],
                'features' => ['snmp', 'telnet', 'ssh'],
            ],
        ];
    }

    /**
     * Check if brand supports specific feature
     */
    public static function supportsFeature(string $brand, string $feature): bool
    {
        $brands = self::getSupportedBrands();
        
        if (!isset($brands[$brand])) {
            return false;
        }

        return in_array($feature, $brands[$brand]['features']);
    }
}
