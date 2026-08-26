<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PopSetting;
use App\Helpers\Mikrotik\MikrotikService;
use Illuminate\Support\Facades\Log;

class CustomerUnsuspendService
{
    /**
     * Default profile name used for isolir (suspend) in Mikrotik
     */
    const DEFAULT_ISOLIR_PROFILE = 'isolir';

    /**
     * Get the isolir profile name for a customer's POP
     */
    protected function getIsolirProfileName(Customer $customer): string
    {
        $popSetting = PopSetting::where('user_id', $customer->pop_id)->first();
        return $popSetting->isolir_profile_name ?? self::DEFAULT_ISOLIR_PROFILE;
    }

    /**
     * Unsuspend customer: restore PPPoE profile to package profile + reactivate
     *
     * Flow:
     * 1. Get PPP secret by username
     * 2. Change profile back to package's original profile
     * 3. Delete from active connections (force reconnect with new profile)
     * 4. Update customer status to active
     *
     * @return string Result status: 'unsuspended', 'no_router', 'not_found', 'not_connected', 'error'
     */
    public function unsuspend(Customer $customer): string
    {
        $mikrotikResult = 'no_router';

        if ($customer->router && $customer->pppoe_username) {
            $mikrotikResult = $this->unsuspendInMikrotik($customer);
        }

        // Update customer status regardless of Mikrotik result
        $customer->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspend_reason' => null,
            'mikrotik_status' => ($mikrotikResult === 'unsuspended') ? 'enabled' : $customer->mikrotik_status,
        ]);

        Log::info("Customer {$customer->customer_id} unsuspended [mikrotik: {$mikrotikResult}]");

        return $mikrotikResult;
    }

    /**
     * Suspend/Isolir customer: change PPPoE profile to 'isolir' + disconnect
     *
     * Flow:
     * 1. Get PPP secret by username
     * 2. Change profile to 'isolir'
     * 3. Delete from active connections (force reconnect with isolir profile)
     * 4. Customer will reconnect and get isolir profile (proxy redirect, etc.)
     *
     * @return string Result status: 'isolated', 'no_router', 'not_found', 'not_connected', 'error'
     */
    public function isolir(Customer $customer): string
    {
        if (!$customer->router || !$customer->pppoe_username) {
            return 'no_router';
        }

        return $this->isolirInMikrotik($customer);
    }

    /**
     * Unsuspend in Mikrotik: change profile back to package profile + disconnect
     */
    protected function unsuspendInMikrotik(Customer $customer): string
    {
        try {
            $router = $customer->router;

            if (!$router || !$router->is_active) {
                return 'no_router';
            }

            $mikrotik = new MikrotikService();

            if (!$mikrotik->connectRouter($router)) {
                Log::warning("Unsuspend: Cannot connect to router {$router->name} for {$customer->customer_id}");
                return 'not_connected';
            }

            // Lookup PPP secret by username
            $secret = $mikrotik->getPppSecretByName($customer->pppoe_username);

            if (!$secret) {
                Log::warning("Unsuspend: PPP secret not found for {$customer->pppoe_username} on {$router->name}");
                return 'not_found';
            }

            $secretId = $secret['.id'] ?? null;
            if (!$secretId) {
                return 'not_found';
            }

            // Get the original package profile name
            $packageProfile = $customer->package?->name;
            if (!$packageProfile) {
                Log::warning("Unsuspend: No package profile found for {$customer->customer_id}");
                return 'error';
            }

            // Change profile back to the original package profile
            if (!$mikrotik->updatePppSecret($secretId, [
                'profile' => $packageProfile,
            ])) {
                return 'error';
            }

            // Also make sure the secret is enabled (in case it was also disabled)
            if (!$mikrotik->enablePppSecret($secretId)) {
                return 'error';
            }

            // Delete from active connections to force reconnect with new profile
            $this->disconnectActiveSession($mikrotik, $customer->pppoe_username);

            Log::info("Unsuspend: PPP secret {$customer->pppoe_username} profile changed to '{$packageProfile}' and disconnected");

            return 'unsuspended';

        } catch (\Exception $e) {
            Log::error("Mikrotik unsuspend failed for {$customer->customer_id}: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Isolir in Mikrotik: change profile to 'isolir' + disconnect
     */
    protected function isolirInMikrotik(Customer $customer): string
    {
        try {
            $router = $customer->router;

            if (!$router || !$router->is_active) {
                return 'no_router';
            }

            $mikrotik = new MikrotikService();

            if (!$mikrotik->connectRouter($router)) {
                Log::warning("Isolir: Cannot connect to router {$router->name} for {$customer->customer_id}");
                return 'not_connected';
            }

            // Lookup PPP secret by username
            $secret = $mikrotik->getPppSecretByName($customer->pppoe_username);

            if (!$secret) {
                Log::warning("Isolir: PPP secret not found for {$customer->pppoe_username} on {$router->name}");
                return 'not_found';
            }

            $secretId = $secret['.id'] ?? null;
            if (!$secretId) {
                return 'not_found';
            }

            // Change profile to isolir
            $isolirProfile = $this->getIsolirProfileName($customer);
            if (!$mikrotik->updatePppSecret($secretId, [
                'profile' => $isolirProfile,
            ])) {
                return 'error';
            }

            // Delete from active connections to force reconnect with isolir profile
            $this->disconnectActiveSession($mikrotik, $customer->pppoe_username);

            Log::info("Isolir: PPP secret {$customer->pppoe_username} profile changed to '{$isolirProfile}' and disconnected");

            return 'isolated';

        } catch (\Exception $e) {
            Log::error("Mikrotik isolir failed for {$customer->customer_id}: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Disconnect active PPP session by username
     */
    protected function disconnectActiveSession(MikrotikService $mikrotik, string $username): void
    {
        $activeConnections = $mikrotik->getPppActive();
        foreach ($activeConnections as $conn) {
            if (($conn['name'] ?? '') === $username && isset($conn['.id'])) {
                $mikrotik->disconnectPppUser($conn['.id']);
                break;
            }
        }
    }
}
