<?php

namespace App\Helpers\Mikrotik;

use App\Models\Router;
use Exception;

/**
 * Mikrotik Router Service
 * High-level API for managing Mikrotik routers
 */
class MikrotikService
{
    protected MikrotikAPI $api;
    protected ?Router $router = null;

    public function __construct()
    {
        $this->api = new MikrotikAPI();
    }

    /**
     * Connect to router using Router model
     */
    public function connectRouter(Router $router): bool
    {
        $this->router = $router;
        
        $connected = $this->api->connect(
            $router->host,
            $router->username,
            $router->decrypted_password,
            $router->use_ssl ? $router->api_ssl_port : $router->api_port,
            $router->use_ssl
        );

        if ($connected) {
            // Update router info
            $resource = $this->api->getSystemResource();
            $router->update([
                'identity' => $this->api->getIdentity(),
                'ros_version' => $resource['version'] ?? null,
                'ros_major_version' => $this->api->getMajorVersion(),
                'board_name' => $resource['board-name'] ?? null,
                'architecture' => $resource['architecture-name'] ?? null,
                'cpu' => $resource['cpu'] ?? null,
                'total_memory' => $resource['total-memory'] ?? null,
                'free_memory' => $resource['free-memory'] ?? null,
                'total_hdd_space' => $resource['total-hdd-space'] ?? null,
                'free_hdd_space' => $resource['free-hdd-space'] ?? null,
                'uptime' => $resource['uptime'] ?? null,
                'status' => 'online',
                'last_connected_at' => now(),
            ]);
        }

        return $connected;
    }

    /**
     * Test connection to router
     */
    public function testConnection(string $host, string $username, string $password, int $port = 8728, bool $ssl = false): array
    {
        try {
            $connected = $this->api->connect($host, $username, $password, $port, $ssl);
            
            if ($connected) {
                $resource = $this->api->getSystemResource();
                $identity = $this->api->getIdentity();
                
                return [
                    'success' => true,
                    'message' => 'Koneksi berhasil!',
                    'data' => [
                        'identity' => $identity,
                        'version' => $resource['version'] ?? null,
                        'major_version' => $this->api->getMajorVersion(),
                        'board_name' => $resource['board-name'] ?? null,
                        'architecture' => $resource['architecture-name'] ?? null,
                        'cpu' => $resource['cpu'] ?? null,
                        'total_memory' => $resource['total-memory'] ?? null,
                        'free_memory' => $resource['free-memory'] ?? null,
                        'uptime' => $resource['uptime'] ?? null,
                    ]
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Gagal login ke router. Periksa username dan password.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get system resource
     */
    public function getSystemResource(): array
    {
        return $this->api->getSystemResource();
    }

    /**
     * Get all interfaces
     */
    public function getInterfaces(): array
    {
        return $this->api->exec('/interface/print');
    }

    /**
     * Get interface by name
     */
    public function getInterface(string $name): ?array
    {
        $result = $this->api->exec('/interface/print', [
            '?name=' . $name
        ]);
        return $result[0] ?? null;
    }

    /**
     * Enable interface
     */
    public function enableInterface(string $id): bool
    {
        $result = $this->api->exec('/interface/enable', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Disable interface
     */
    public function disableInterface(string $id): bool
    {
        $result = $this->api->exec('/interface/disable', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Update interface
     */
    public function updateInterface(string $id, array $params): bool
    {
        $params['.id'] = $id;
        $result = $this->api->exec('/interface/set', $params);
        return !isset($result[0]['_error']);
    }

    /**
     * Get IP addresses
     */
    public function getIpAddresses(): array
    {
        return $this->api->exec('/ip/address/print');
    }

    /**
     * Add IP address
     */
    public function addIpAddress(string $address, string $interface, ?string $comment = null): array
    {
        $params = [
            'address' => $address,
            'interface' => $interface,
        ];
        if ($comment) {
            $params['comment'] = $comment;
        }
        return $this->api->exec('/ip/address/add', $params);
    }

    /**
     * Remove IP address
     */
    public function removeIpAddress(string $id): bool
    {
        $result = $this->api->exec('/ip/address/remove', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Update IP address
     */
    public function updateIpAddress(string $id, array $params): bool
    {
        $params['.id'] = $id;
        $result = $this->api->exec('/ip/address/set', $params);
        return !isset($result[0]['_error']);
    }

    /**
     * Enable IP address
     */
    public function enableIpAddress(string $id): bool
    {
        $result = $this->api->exec('/ip/address/enable', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Disable IP address
     */
    public function disableIpAddress(string $id): bool
    {
        $result = $this->api->exec('/ip/address/disable', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Get PPP secrets
     */
    public function getPppSecrets(): array
    {
        return $this->api->exec('/ppp/secret/print');
    }

    /**
     * Add PPP secret
     */
    public function addPppSecret(array $params): array
    {
        return $this->api->exec('/ppp/secret/add', $params);
    }

    /**
     * Update PPP secret
     */
    public function updatePppSecret(string $id, array $params): bool
    {
        $params['.id'] = $id;
        $result = $this->api->exec('/ppp/secret/set', $params);
        return !isset($result[0]['_error']);
    }

    /**
     * Remove PPP secret
     */
    public function removePppSecret(string $id): bool
    {
        $result = $this->api->exec('/ppp/secret/remove', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Enable PPP secret
     */
    public function enablePppSecret(string $id): bool
    {
        $result = $this->api->exec('/ppp/secret/enable', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Disable PPP secret
     */
    public function disablePppSecret(string $id): bool
    {
        $result = $this->api->exec('/ppp/secret/disable', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Get PPP active connections
     */
    public function getPppActive(): array
    {
        return $this->api->exec('/ppp/active/print');
    }

    /**
     * Remove PPP active (disconnect user)
     */
    public function disconnectPppUser(string $id): bool
    {
        $result = $this->api->exec('/ppp/active/remove', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Get PPP profiles
     */
    public function getPppProfiles(): array
    {
        return $this->api->exec('/ppp/profile/print');
    }

    /**
     * Add PPP profile
     */
    public function addPppProfile(array $params): array
    {
        return $this->api->exec('/ppp/profile/add', $params);
    }

    /**
     * Update PPP profile
     */
    public function updatePppProfile(string $id, array $params): bool
    {
        $params['.id'] = $id;
        $result = $this->api->exec('/ppp/profile/set', $params);
        return !isset($result[0]['_error']);
    }

    /**
     * Remove PPP profile
     */
    public function removePppProfile(string $id): bool
    {
        $result = $this->api->exec('/ppp/profile/remove', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Get PPP profile by name
     */
    public function getPppProfileByName(string $name): ?array
    {
        $result = $this->api->exec('/ppp/profile/print', ['?name=' . $name]);
        return $result[0] ?? null;
    }

    /**
     * Add IP pool
     */
    public function addIpPool(array $params): array
    {
        return $this->api->exec('/ip/pool/add', $params);
    }

    /**
     * Update IP pool
     */
    public function updateIpPool(string $id, array $params): bool
    {
        $params['.id'] = $id;
        $result = $this->api->exec('/ip/pool/set', $params);
        return !isset($result[0]['_error']);
    }

    /**
     * Remove IP pool
     */
    public function removeIpPool(string $id): bool
    {
        $result = $this->api->exec('/ip/pool/remove', ['.id' => $id]);
        return !isset($result[0]['_error']);
    }

    /**
     * Get IP pool by name
     */
    public function getIpPoolByName(string $name): ?array
    {
        $result = $this->api->exec('/ip/pool/print', ['?name=' . $name]);
        return $result[0] ?? null;
    }

    /**
     * Get used IP addresses from pool
     */
    public function getIpPoolUsed(string $poolName): array
    {
        return $this->api->exec('/ip/pool/used/print', ['?pool=' . $poolName]);
    }

    /**
     * Get IP routes
     */
    public function getRoutes(): array
    {
        return $this->api->exec('/ip/route/print');
    }

    /**
     * Get firewall filter rules
     */
    public function getFirewallFilter(): array
    {
        return $this->api->exec('/ip/firewall/filter/print');
    }

    /**
     * Get firewall NAT rules
     */
    public function getFirewallNat(): array
    {
        return $this->api->exec('/ip/firewall/nat/print');
    }

    /**
     * Get firewall mangle rules
     */
    public function getFirewallMangle(): array
    {
        return $this->api->exec('/ip/firewall/mangle/print');
    }

    /**
     * Get queues
     */
    public function getQueues(): array
    {
        return $this->api->exec('/queue/simple/print');
    }

    /**
     * Get DHCP leases
     */
    public function getDhcpLeases(): array
    {
        return $this->api->exec('/ip/dhcp-server/lease/print');
    }

    /**
     * Get ARP list
     */
    public function getArpList(): array
    {
        return $this->api->exec('/ip/arp/print');
    }

    /**
     * Get logs
     */
    public function getLogs(int $limit = 100): array
    {
        return $this->api->exec('/log/print', ['?count=' . $limit]);
    }

    /**
     * Get public IP from Mikrotik Cloud
     */
    public function getCloudPublicIp(): ?string
    {
        try {
            $result = $this->api->exec('/ip/cloud/print');
            if (!empty($result[0]['public-address'])) {
                return $result[0]['public-address'];
            }
        } catch (\Exception $e) {
            // Cloud might not be enabled
        }
        return null;
    }

    /**
     * Get public IP using fetch tool (external service)
     */
    public function fetchPublicIp(): ?string
    {
        try {
            // Try using fetch tool with various IP check services
            $services = [
                'https://api.ipify.org',
                'https://ifconfig.me/ip',
                'https://icanhazip.com',
            ];
            
            foreach ($services as $service) {
                $result = $this->api->exec('/tool/fetch', [
                    'url' => $service,
                    'mode' => 'https',
                    'as-value' => '',
                    'output' => 'user',
                ]);
                
                if (!empty($result[0]['data'])) {
                    $ip = trim($result[0]['data']);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $ip;
                    }
                }
            }
        } catch (\Exception $e) {
            // Fetch might fail
        }
        return null;
    }

    /**
     * Detect public IP (tries cloud first, then fetch)
     */
    public function detectPublicIp(): ?string
    {
        // Try cloud first (fastest)
        $ip = $this->getCloudPublicIp();
        if ($ip) {
            return $ip;
        }
        
        // Try fetch tool
        return $this->fetchPublicIp();
    }

    /**
     * Get DHCP client info (for getting gateway from ISP)
     */
    public function getDhcpClients(): array
    {
        return $this->api->exec('/ip/dhcp-client/print');
    }

    /**
     * Get PPPoE client info
     */
    public function getPppoeClients(): array
    {
        return $this->api->exec('/interface/pppoe-client/print');
    }

    /**
     * Get DNS servers
     */
    public function getDnsServers(): array
    {
        return $this->api->exec('/ip/dns/print');
    }

    /**
     * Get firewall address lists
     */
    public function getFirewallAddressList(): array
    {
        return $this->api->exec('/ip/firewall/address-list/print');
    }

    /**
     * Get hotspot users
     */
    public function getHotspotUsers(): array
    {
        return $this->api->exec('/ip/hotspot/user/print');
    }

    /**
     * Get hotspot active
     */
    public function getHotspotActive(): array
    {
        return $this->api->exec('/ip/hotspot/active/print');
    }

    /**
     * Get bridge interfaces
     */
    public function getBridges(): array
    {
        return $this->api->exec('/interface/bridge/print');
    }

    /**
     * Get bridge ports
     */
    public function getBridgePorts(): array
    {
        return $this->api->exec('/interface/bridge/port/print');
    }

    /**
     * Get VLANs
     */
    public function getVlans(): array
    {
        return $this->api->exec('/interface/vlan/print');
    }

    /**
     * Get Ethernet interfaces
     */
    public function getEthernets(): array
    {
        return $this->api->exec('/interface/ethernet/print');
    }

    /**
     * Get wireless interfaces
     */
    public function getWirelessInterfaces(): array
    {
        return $this->api->exec('/interface/wireless/print');
    }

    /**
     * Get wireless registration table
     */
    public function getWirelessRegistration(): array
    {
        return $this->api->exec('/interface/wireless/registration-table/print');
    }

    /**
     * Get system scheduler
     */
    public function getScheduler(): array
    {
        return $this->api->exec('/system/scheduler/print');
    }

    /**
     * Get system scripts
     */
    public function getScripts(): array
    {
        return $this->api->exec('/system/script/print');
    }

    /**
     * Get system users
     */
    public function getSystemUsers(): array
    {
        return $this->api->exec('/user/print');
    }

    /**
     * Get system packages
     */
    public function getPackages(): array
    {
        return $this->api->exec('/system/package/print');
    }

    /**
     * Get IP pools
     */
    public function getIpPools(): array
    {
        return $this->api->exec('/ip/pool/print');
    }

    /**
     * Get DHCP servers
     */
    public function getDhcpServers(): array
    {
        return $this->api->exec('/ip/dhcp-server/print');
    }

    /**
     * Get DHCP server networks
     */
    public function getDhcpNetworks(): array
    {
        return $this->api->exec('/ip/dhcp-server/network/print');
    }

    /**
     * Execute ping command
     * @param string $address Target address to ping
     * @param int $count Number of ping packets
     * @return array Ping results
     */
    public function ping(string $address, int $count = 4): array
    {
        return $this->api->exec('/ping', [
            'address' => $address,
            'count' => (string) $count,
        ]);
    }

    /**
     * Execute custom command
     */
    public function executeCommand(string $command, array $params = []): array
    {
        return $this->api->exec($command, $params);
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->api->isConnected();
    }

    /**
     * Is ROS 7
     */
    public function isRos7(): bool
    {
        return $this->api->isRos7();
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        $this->api->disconnect();
    }

    /**
     * Get API instance for direct access
     */
    public function getApi(): MikrotikAPI
    {
        return $this->api;
    }
}
