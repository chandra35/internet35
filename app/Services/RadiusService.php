<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PopSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class RadiusService
{
    protected $connection;
    protected $popSetting;
    protected $connected = false;

    /**
     * Connect to FreeRadius database
     */
    public function connect(PopSetting $popSetting): bool
    {
        $this->popSetting = $popSetting;
        
        if (!$popSetting->radius_enabled) {
            Log::info('Radius is not enabled for this POP');
            return false;
        }
        
        try {
            // Create dynamic database connection
            $config = [
                'driver' => 'mysql',
                'host' => $popSetting->radius_host,
                'port' => $popSetting->radius_port ?? 3306,
                'database' => $popSetting->radius_database ?? 'radius',
                'username' => $popSetting->radius_username,
                'password' => $this->decryptPassword($popSetting->radius_password),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ];
            
            config(['database.connections.radius' => $config]);
            
            // Test connection
            DB::connection('radius')->getPdo();
            $this->connected = true;
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to connect to Radius database: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt password
     */
    protected function decryptPassword(?string $password): ?string
    {
        if (!$password) {
            return null;
        }
        
        try {
            return Crypt::decryptString($password);
        } catch (\Exception $e) {
            return $password;
        }
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Create Radius user (radcheck)
     */
    public function createUser(Customer $customer, Package $package): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to Radius database'];
        }

        try {
            $username = $customer->pppoe_username;
            $password = $customer->decrypted_pppoe_password;
            
            // Check if user exists
            $exists = DB::connection('radius')
                ->table('radcheck')
                ->where('username', $username)
                ->exists();
            
            if ($exists) {
                return ['success' => false, 'message' => 'Username already exists in Radius'];
            }
            
            DB::connection('radius')->beginTransaction();
            
            // Insert into radcheck (authentication)
            DB::connection('radius')->table('radcheck')->insert([
                'username' => $username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $password,
            ]);
            
            // Insert into radreply (attributes returned to NAS)
            $replyAttributes = $this->getReplyAttributes($customer, $package);
            foreach ($replyAttributes as $attr) {
                DB::connection('radius')->table('radreply')->insert([
                    'username' => $username,
                    'attribute' => $attr['attribute'],
                    'op' => $attr['op'] ?? '=',
                    'value' => $attr['value'],
                ]);
            }
            
            // Insert into radusergroup (group membership for bandwidth profile)
            if ($package->profile_name) {
                DB::connection('radius')->table('radusergroup')->insert([
                    'username' => $username,
                    'groupname' => $package->profile_name,
                    'priority' => 1,
                ]);
            }
            
            // Insert into userinfo (optional user information)
            DB::connection('radius')->table('userinfo')->insert([
                'username' => $username,
                'firstname' => $customer->name,
                'lastname' => '',
                'email' => $customer->email ?? '',
                'department' => $customer->customer_id,
                'company' => $this->popSetting->isp_name ?? '',
                'workphone' => $customer->phone ?? '',
                'homephone' => $customer->phone_alt ?? '',
                'mobilephone' => $customer->phone ?? '',
                'address' => $customer->address ?? '',
                'city' => '',
                'state' => '',
                'country' => 'Indonesia',
                'zip' => $customer->postal_code ?? '',
                'notes' => "Customer ID: {$customer->customer_id}",
                'creationdate' => now()->format('Y-m-d'),
                'creationby' => 'internet35',
                'updatedate' => now()->format('Y-m-d'),
            ]);
            
            DB::connection('radius')->commit();
            
            return ['success' => true, 'message' => 'Radius user created successfully'];
        } catch (\Exception $e) {
            DB::connection('radius')->rollBack();
            Log::error('Failed to create Radius user: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update Radius user
     */
    public function updateUser(Customer $customer, Package $package, ?string $oldUsername = null): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to Radius database'];
        }

        try {
            $searchUsername = $oldUsername ?? $customer->pppoe_username;
            $newUsername = $customer->pppoe_username;
            
            // Check if user exists
            $exists = DB::connection('radius')
                ->table('radcheck')
                ->where('username', $searchUsername)
                ->exists();
            
            if (!$exists) {
                // User doesn't exist, create new
                return $this->createUser($customer, $package);
            }
            
            DB::connection('radius')->beginTransaction();
            
            // Update radcheck (password and username)
            DB::connection('radius')
                ->table('radcheck')
                ->where('username', $searchUsername)
                ->update([
                    'username' => $newUsername,
                    'value' => $customer->decrypted_pppoe_password,
                ]);
            
            // Update radreply
            DB::connection('radius')
                ->table('radreply')
                ->where('username', $searchUsername)
                ->delete();
            
            $replyAttributes = $this->getReplyAttributes($customer, $package);
            foreach ($replyAttributes as $attr) {
                DB::connection('radius')->table('radreply')->insert([
                    'username' => $newUsername,
                    'attribute' => $attr['attribute'],
                    'op' => $attr['op'] ?? '=',
                    'value' => $attr['value'],
                ]);
            }
            
            // Update radusergroup
            DB::connection('radius')
                ->table('radusergroup')
                ->where('username', $searchUsername)
                ->update([
                    'username' => $newUsername,
                    'groupname' => $package->profile_name ?? 'default',
                ]);
            
            // Update userinfo
            DB::connection('radius')
                ->table('userinfo')
                ->where('username', $searchUsername)
                ->update([
                    'username' => $newUsername,
                    'firstname' => $customer->name,
                    'email' => $customer->email ?? '',
                    'workphone' => $customer->phone ?? '',
                    'mobilephone' => $customer->phone ?? '',
                    'address' => $customer->address ?? '',
                    'updatedate' => now()->format('Y-m-d'),
                ]);
            
            DB::connection('radius')->commit();
            
            return ['success' => true, 'message' => 'Radius user updated successfully'];
        } catch (\Exception $e) {
            DB::connection('radius')->rollBack();
            Log::error('Failed to update Radius user: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete Radius user
     */
    public function deleteUser(string $username): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to Radius database'];
        }

        try {
            DB::connection('radius')->beginTransaction();
            
            // Delete from all tables
            DB::connection('radius')->table('radcheck')->where('username', $username)->delete();
            DB::connection('radius')->table('radreply')->where('username', $username)->delete();
            DB::connection('radius')->table('radusergroup')->where('username', $username)->delete();
            DB::connection('radius')->table('userinfo')->where('username', $username)->delete();
            
            // Also delete accounting records (optional)
            DB::connection('radius')->table('radacct')->where('username', $username)->delete();
            DB::connection('radius')->table('radpostauth')->where('username', $username)->delete();
            
            DB::connection('radius')->commit();
            
            return ['success' => true, 'message' => 'Radius user deleted successfully'];
        } catch (\Exception $e) {
            DB::connection('radius')->rollBack();
            Log::error('Failed to delete Radius user: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Enable/Disable Radius user
     */
    public function toggleUser(string $username, bool $enable): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to Radius database'];
        }

        try {
            if ($enable) {
                // Remove Auth-Type := Reject
                DB::connection('radius')
                    ->table('radcheck')
                    ->where('username', $username)
                    ->where('attribute', 'Auth-Type')
                    ->where('value', 'Reject')
                    ->delete();
            } else {
                // Check if already disabled
                $disabled = DB::connection('radius')
                    ->table('radcheck')
                    ->where('username', $username)
                    ->where('attribute', 'Auth-Type')
                    ->where('value', 'Reject')
                    ->exists();
                
                if (!$disabled) {
                    // Add Auth-Type := Reject to disable
                    DB::connection('radius')->table('radcheck')->insert([
                        'username' => $username,
                        'attribute' => 'Auth-Type',
                        'op' => ':=',
                        'value' => 'Reject',
                    ]);
                }
            }
            
            return ['success' => true, 'message' => 'Radius user ' . ($enable ? 'enabled' : 'disabled')];
        } catch (\Exception $e) {
            Log::error('Failed to toggle Radius user: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get reply attributes based on customer and package
     */
    protected function getReplyAttributes(Customer $customer, Package $package): array
    {
        $attributes = [];
        
        // Framed-IP-Address if static IP
        if ($customer->remote_address) {
            $attributes[] = [
                'attribute' => 'Framed-IP-Address',
                'value' => $customer->remote_address,
            ];
        }
        
        // Rate limit using Mikrotik-Rate-Limit attribute
        if ($package->rate_limit) {
            $attributes[] = [
                'attribute' => 'Mikrotik-Rate-Limit',
                'value' => $package->rate_limit,
            ];
        }
        
        // Session timeout (for prepaid)
        if ($customer->active_until) {
            $secondsRemaining = now()->diffInSeconds($customer->active_until, false);
            if ($secondsRemaining > 0) {
                $attributes[] = [
                    'attribute' => 'Session-Timeout',
                    'value' => (string) min($secondsRemaining, 86400), // Max 1 day
                ];
            }
        }
        
        return $attributes;
    }

    /**
     * Send CoA (Change of Authorization) to disconnect user
     */
    public function disconnectUser(string $username): array
    {
        if (!$this->popSetting || !$this->popSetting->radius_nas_ip) {
            return ['success' => false, 'message' => 'NAS IP not configured'];
        }

        try {
            // Get session info
            $session = DB::connection('radius')
                ->table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->first();
            
            if (!$session) {
                return ['success' => true, 'message' => 'No active session found'];
            }
            
            // Send CoA disconnect using radclient (requires exec)
            $nasIp = $this->popSetting->radius_nas_ip;
            $coaPort = $this->popSetting->radius_coa_port ?? 3799;
            $secret = $this->decryptPassword($this->popSetting->radius_nas_secret);
            
            $cmd = sprintf(
                'echo "User-Name=%s,Acct-Session-Id=%s" | radclient -x %s:%d disconnect %s 2>&1',
                escapeshellarg($username),
                escapeshellarg($session->acctsessionid),
                escapeshellarg($nasIp),
                $coaPort,
                escapeshellarg($secret)
            );
            
            $output = shell_exec($cmd);
            
            return ['success' => true, 'message' => 'Disconnect CoA sent', 'output' => $output];
        } catch (\Exception $e) {
            Log::error('Failed to send CoA disconnect: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if username exists in Radius
     */
    public function userExists(string $username): bool
    {
        if (!$this->connected) {
            return false;
        }

        return DB::connection('radius')
            ->table('radcheck')
            ->where('username', $username)
            ->exists();
    }

    /**
     * Get user's current session
     */
    public function getActiveSession(string $username): ?object
    {
        if (!$this->connected) {
            return null;
        }

        return DB::connection('radius')
            ->table('radacct')
            ->where('username', $username)
            ->whereNull('acctstoptime')
            ->first();
    }

    /**
     * Check if user is online
     */
    public function isOnline(string $username): bool
    {
        return $this->getActiveSession($username) !== null;
    }

    /**
     * Get user's accounting records
     */
    public function getAccountingHistory(string $username, int $limit = 30): array
    {
        if (!$this->connected) {
            return [];
        }

        return DB::connection('radius')
            ->table('radacct')
            ->where('username', $username)
            ->orderBy('acctstarttime', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get user's total usage
     */
    public function getTotalUsage(string $username, ?string $startDate = null, ?string $endDate = null): array
    {
        if (!$this->connected) {
            return ['upload' => 0, 'download' => 0, 'total' => 0];
        }

        $query = DB::connection('radius')
            ->table('radacct')
            ->where('username', $username);
        
        if ($startDate) {
            $query->where('acctstarttime', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('acctstarttime', '<=', $endDate);
        }
        
        $result = $query->selectRaw('
            COALESCE(SUM(acctinputoctets), 0) as upload,
            COALESCE(SUM(acctoutputoctets), 0) as download
        ')->first();
        
        return [
            'upload' => (int) $result->upload,
            'download' => (int) $result->download,
            'total' => (int) ($result->upload + $result->download),
        ];
    }

    /**
     * Create Radius group (for package bandwidth profiles)
     */
    public function createGroup(string $groupName, string $rateLimit): array
    {
        if (!$this->connected) {
            return ['success' => false, 'message' => 'Not connected to Radius database'];
        }

        try {
            // Check if group exists
            $exists = DB::connection('radius')
                ->table('radgroupreply')
                ->where('groupname', $groupName)
                ->exists();
            
            if ($exists) {
                // Update existing
                DB::connection('radius')
                    ->table('radgroupreply')
                    ->where('groupname', $groupName)
                    ->where('attribute', 'Mikrotik-Rate-Limit')
                    ->update(['value' => $rateLimit]);
            } else {
                // Create new
                DB::connection('radius')->table('radgroupreply')->insert([
                    'groupname' => $groupName,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $rateLimit,
                ]);
            }
            
            return ['success' => true, 'message' => 'Group created/updated successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to create Radius group: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Test connection to Radius database
     */
    public static function testConnection(array $config): array
    {
        try {
            $testConfig = [
                'driver' => 'mysql',
                'host' => $config['host'],
                'port' => $config['port'] ?? 3306,
                'database' => $config['database'] ?? 'radius',
                'username' => $config['username'],
                'password' => $config['password'],
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ];
            
            config(['database.connections.radius_test' => $testConfig]);
            
            DB::connection('radius_test')->getPdo();
            
            // Check required tables
            $tables = ['radcheck', 'radreply', 'radusergroup', 'radacct'];
            $missingTables = [];
            
            foreach ($tables as $table) {
                if (!DB::connection('radius_test')->getSchemaBuilder()->hasTable($table)) {
                    $missingTables[] = $table;
                }
            }
            
            DB::purge('radius_test');
            
            if (!empty($missingTables)) {
                return [
                    'success' => false,
                    'message' => 'Missing required tables: ' . implode(', ', $missingTables),
                ];
            }
            
            return ['success' => true, 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
