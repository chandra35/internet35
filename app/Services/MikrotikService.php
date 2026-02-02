<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Customer;
use App\Models\Package;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class MikrotikService
{
    protected $socket;
    protected $router;
    protected $connected = false;
    protected $debug = false;
    protected $timeout = 10;
    protected $attempts = 3;
    protected $delay = 1;

    /**
     * Connect to Mikrotik router
     */
    public function connect(Router $router): bool
    {
        $this->router = $router;
        
        // Get decrypted password
        $password = $router->decrypted_password;
        
        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            try {
                $this->socket = @fsockopen(
                    $router->host,
                    $router->port,
                    $errno,
                    $errstr,
                    $this->timeout
                );
                
                if ($this->socket === false) {
                    throw new \Exception("Cannot connect to {$router->host}:{$router->port} - {$errstr}");
                }
                
                // Set socket options
                stream_set_timeout($this->socket, $this->timeout);
                
                // Login
                if ($this->login($router->username, $password)) {
                    $this->connected = true;
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning("Mikrotik connection attempt {$attempt} failed: " . $e->getMessage());
                if ($attempt < $this->attempts) {
                    sleep($this->delay);
                }
            }
        }
        
        return false;
    }

    /**
     * Login to router
     */
    protected function login(string $username, string $password): bool
    {
        // Send login command
        $this->write('/login', false);
        $this->write('=name=' . $username, false);
        $this->write('=password=' . $password);
        
        $response = $this->read();
        
        return isset($response[0]) && $response[0] === '!done';
    }

    /**
     * Disconnect from router
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }

    /**
     * Write command to socket
     */
    protected function write(string $command, bool $lastWord = true): bool
    {
        $length = strlen($command);
        
        if ($length < 0x80) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 0x4000) {
            fwrite($this->socket, chr(($length >> 8) | 0x80) . chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            fwrite($this->socket, chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } elseif ($length < 0x10000000) {
            fwrite($this->socket, chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
        }
        
        fwrite($this->socket, $command);
        
        if ($lastWord) {
            fwrite($this->socket, chr(0));
        }
        
        return true;
    }

    /**
     * Read response from socket
     */
    protected function read(): array
    {
        $response = [];
        
        while (true) {
            $byte = ord(fread($this->socket, 1));
            
            if ($byte === 0) {
                break;
            }
            
            // Determine length
            if ($byte < 0x80) {
                $length = $byte;
            } elseif ($byte < 0xC0) {
                $length = (($byte & 0x3F) << 8) + ord(fread($this->socket, 1));
            } elseif ($byte < 0xE0) {
                $length = (($byte & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            } elseif ($byte < 0xF0) {
                $length = (($byte & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            } else {
                $length = (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            }
            
            $word = fread($this->socket, $length);
            $response[] = $word;
            
            if ($word === '!done' || $word === '!trap' || $word === '!fatal') {
                break;
            }
        }
        
        return $response;
    }

    /**
     * Send command to router
     */
    public function command(string $cmd, array $params = []): array
    {
        $this->write($cmd, empty($params));
        
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $this->write($value, $key === array_key_last($params));
            } else {
                $isLast = $key === array_key_last(array_keys($params));
                $this->write('=' . $key . '=' . $value, $isLast);
            }
        }
        
        return $this->parseResponse($this->read());
    }

    /**
     * Parse response into array
     */
    protected function parseResponse(array $response): array
    {
        $result = [];
        $current = [];
        
        foreach ($response as $word) {
            if ($word === '!re') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                $current = [];
            } elseif (strpos($word, '=') === 0) {
                $parts = explode('=', substr($word, 1), 2);
                if (count($parts) === 2) {
                    $current[$parts[0]] = $parts[1];
                }
            } elseif ($word === '!done') {
                if (!empty($current)) {
                    $result[] = $current;
                }
            } elseif ($word === '!trap') {
                $current['error'] = true;
            }
        }
        
        return $result;
    }

    /**
     * Create PPP Secret
     */
    public function createPPPSecret(Customer $customer, Package $package): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to router'];
        }

        try {
            $params = [
                'name' => $customer->pppoe_username,
                'password' => $customer->decrypted_pppoe_password,
                'service' => $customer->service_type ?? 'pppoe',
                'profile' => $package->profile_name ?? 'default',
                'comment' => "Customer: {$customer->name} ({$customer->customer_id})",
            ];
            
            // Add remote address if specified
            if ($customer->remote_address) {
                $params['remote-address'] = $customer->remote_address;
            }
            
            $result = $this->command('/ppp/secret/add', $params);
            
            if (isset($result[0]['error'])) {
                return ['success' => false, 'message' => $result[0]['message'] ?? 'Failed to create PPP secret'];
            }
            
            return ['success' => true, 'message' => 'PPP secret created successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to create PPP secret: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update PPP Secret
     */
    public function updatePPPSecret(Customer $customer, Package $package, ?string $oldUsername = null): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to router'];
        }

        try {
            $searchName = $oldUsername ?? $customer->pppoe_username;
            
            // Find existing secret
            $secrets = $this->command('/ppp/secret/print', ['?name' => $searchName]);
            
            if (empty($secrets)) {
                // Secret not found, create new one
                return $this->createPPPSecret($customer, $package);
            }
            
            $secretId = $secrets[0]['.id'];
            
            $params = [
                '.id' => $secretId,
                'name' => $customer->pppoe_username,
                'service' => $customer->service_type ?? 'pppoe',
                'profile' => $package->profile_name ?? 'default',
                'comment' => "Customer: {$customer->name} ({$customer->customer_id})",
            ];
            
            // Only update password if changed
            if ($customer->wasChanged('pppoe_password')) {
                $params['password'] = $customer->decrypted_pppoe_password;
            }
            
            if ($customer->remote_address) {
                $params['remote-address'] = $customer->remote_address;
            }
            
            $result = $this->command('/ppp/secret/set', $params);
            
            return ['success' => true, 'message' => 'PPP secret updated successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to update PPP secret: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete PPP Secret
     */
    public function deletePPPSecret(string $username): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to router'];
        }

        try {
            // Find secret
            $secrets = $this->command('/ppp/secret/print', ['?name' => $username]);
            
            if (empty($secrets)) {
                return ['success' => true, 'message' => 'PPP secret not found (already deleted)'];
            }
            
            $this->command('/ppp/secret/remove', ['.id' => $secrets[0]['.id']]);
            
            return ['success' => true, 'message' => 'PPP secret deleted successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to delete PPP secret: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Enable/Disable PPP Secret
     */
    public function togglePPPSecret(string $username, bool $enable): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to router'];
        }

        try {
            $secrets = $this->command('/ppp/secret/print', ['?name' => $username]);
            
            if (empty($secrets)) {
                return ['success' => false, 'message' => 'PPP secret not found'];
            }
            
            $command = $enable ? '/ppp/secret/enable' : '/ppp/secret/disable';
            $this->command($command, ['.id' => $secrets[0]['.id']]);
            
            return ['success' => true, 'message' => 'PPP secret ' . ($enable ? 'enabled' : 'disabled')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Disconnect active PPP session
     */
    public function disconnectPPPSession(string $username): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to router'];
        }

        try {
            // Find active session
            $sessions = $this->command('/ppp/active/print', ['?name' => $username]);
            
            if (empty($sessions)) {
                return ['success' => true, 'message' => 'No active session found'];
            }
            
            $this->command('/ppp/active/remove', ['.id' => $sessions[0]['.id']]);
            
            return ['success' => true, 'message' => 'PPP session disconnected'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get PPP Secret info
     */
    public function getPPPSecret(string $username): ?array
    {
        if (!$this->connected) {
            return null;
        }

        try {
            $secrets = $this->command('/ppp/secret/print', ['?name' => $username]);
            return $secrets[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if PPP secret exists
     */
    public function pppSecretExists(string $username): bool
    {
        return $this->getPPPSecret($username) !== null;
    }

    /**
     * Get PPP profiles
     */
    public function getPPPProfiles(): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            $profiles = $this->command('/ppp/profile/print');
            return ['success' => true, 'data' => $profiles];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create PPP Profile
     */
    public function createPPPProfile(string $name, ?string $localAddress = null, ?string $remoteAddress = null, ?string $rateLimit = null, ?string $comment = null): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            $params = ['name' => $name];
            
            if ($localAddress) {
                $params['local-address'] = $localAddress;
            }
            if ($remoteAddress) {
                $params['remote-address'] = $remoteAddress;
            }
            if ($rateLimit) {
                $params['rate-limit'] = $rateLimit;
            }
            if ($comment) {
                $params['comment'] = $comment;
            }
            
            $result = $this->command('/ppp/profile/add', $params);
            
            if (isset($result[0]['error'])) {
                return ['success' => false, 'error' => $result[0]['message'] ?? 'Failed to create profile'];
            }
            
            return ['success' => true, 'id' => $result[0]['ret'] ?? null];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update PPP Profile
     */
    public function updatePPPProfile(string $idOrName, ?string $localAddress = null, ?string $remoteAddress = null, ?string $rateLimit = null, ?string $comment = null): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            // Find profile by name if not an ID
            $profileId = $idOrName;
            if (!str_starts_with($idOrName, '*')) {
                $profiles = $this->command('/ppp/profile/print', ['?name' => $idOrName]);
                if (empty($profiles)) {
                    return ['success' => false, 'error' => 'Profile not found'];
                }
                $profileId = $profiles[0]['.id'];
            }
            
            $params = ['.id' => $profileId];
            
            if ($localAddress !== null) {
                $params['local-address'] = $localAddress;
            }
            if ($remoteAddress !== null) {
                $params['remote-address'] = $remoteAddress;
            }
            if ($rateLimit !== null) {
                $params['rate-limit'] = $rateLimit;
            }
            if ($comment !== null) {
                $params['comment'] = $comment;
            }
            
            $this->command('/ppp/profile/set', $params);
            
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete PPP Profile
     */
    public function deletePPPProfile(string $idOrName): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            // Find profile by name if not an ID
            $profileId = $idOrName;
            if (!str_starts_with($idOrName, '*')) {
                $profiles = $this->command('/ppp/profile/print', ['?name' => $idOrName]);
                if (empty($profiles)) {
                    return ['success' => false, 'error' => 'Profile not found'];
                }
                $profileId = $profiles[0]['.id'];
            }
            
            $this->command('/ppp/profile/remove', ['.id' => $profileId]);
            
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get IP Pools
     */
    public function getIPPools(): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            $pools = $this->command('/ip/pool/print');
            return ['success' => true, 'data' => $pools];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create IP Pool
     */
    public function createIPPool(string $name, string $ranges, ?string $nextPool = null, ?string $comment = null): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            $params = [
                'name' => $name,
                'ranges' => $ranges,
            ];
            
            if ($nextPool) {
                $params['next-pool'] = $nextPool;
            }
            if ($comment) {
                $params['comment'] = $comment;
            }
            
            $result = $this->command('/ip/pool/add', $params);
            
            if (isset($result[0]['error'])) {
                return ['success' => false, 'error' => $result[0]['message'] ?? 'Failed to create pool'];
            }
            
            return ['success' => true, 'id' => $result[0]['ret'] ?? null];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update IP Pool
     */
    public function updateIPPool(string $idOrName, string $ranges, ?string $nextPool = null, ?string $comment = null): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            // Find pool by name if not an ID
            $poolId = $idOrName;
            if (!str_starts_with($idOrName, '*')) {
                $pools = $this->command('/ip/pool/print', ['?name' => $idOrName]);
                if (empty($pools)) {
                    return ['success' => false, 'error' => 'Pool not found'];
                }
                $poolId = $pools[0]['.id'];
            }
            
            $params = [
                '.id' => $poolId,
                'ranges' => $ranges,
            ];
            
            if ($nextPool !== null) {
                $params['next-pool'] = $nextPool;
            }
            if ($comment !== null) {
                $params['comment'] = $comment;
            }
            
            $this->command('/ip/pool/set', $params);
            
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete IP Pool
     */
    public function deleteIPPool(string $idOrName): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            // Find pool by name if not an ID
            $poolId = $idOrName;
            if (!str_starts_with($idOrName, '*')) {
                $pools = $this->command('/ip/pool/print', ['?name' => $idOrName]);
                if (empty($pools)) {
                    return ['success' => false, 'error' => 'Pool not found'];
                }
                $poolId = $pools[0]['.id'];
            }
            
            $this->command('/ip/pool/remove', ['.id' => $poolId]);
            
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get IP Pool used IPs count
     */
    public function getIPPoolUsed(string $poolName): array
    {
        if (!$this->connected) {
            return ['success' => false, 'error' => 'Not connected to router'];
        }

        try {
            $used = $this->command('/ip/pool/used/print', ['?pool' => $poolName]);
            return ['success' => true, 'used' => count($used)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get active PPP connections
     */
    public function getActivePPP(): array
    {
        if (!$this->connected) {
            return [];
        }

        try {
            return $this->command('/ppp/active/print');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if username is online
     */
    public function isOnline(string $username): bool
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $sessions = $this->command('/ppp/active/print', ['?name' => $username]);
            return !empty($sessions);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
