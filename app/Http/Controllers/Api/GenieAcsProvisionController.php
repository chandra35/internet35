<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Onu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provision data endpoint untuk GenieACS Extension.
 *
 * GenieACS bootstrap provision memanggil endpoint ini (via Node.js http)
 * saat device melakukan 0 BOOTSTRAP (factory reset / pertama kali online).
 * Response digunakan untuk push WAN PPPoE + WLAN secara otomatis.
 *
 * Auth: header X-ACS-Key must match GENIEACS_PROVISION_KEY in .env.
 */
class GenieAcsProvisionController extends Controller
{
    /**
     * GET /api/acs/provision/{serial}
     *
     * Kembalikan konfigurasi provisioning untuk ONU berdasarkan serial number.
     * GenieACS extension memanggil endpoint ini saat bootstrap event.
     */
    public function getProvision(Request $request, string $serial): JsonResponse
    {
        // --- Auth ---
        $configuredKey = config('services.genieacs.provision_key', '');
        if ($configuredKey === '' || $request->header('X-ACS-Key') !== $configuredKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // GenieACS serial format: bisa uppercase hex, trim whitespace
        $serial = trim($serial);
        if ($serial === '') {
            return response()->json(['found' => false]);
        }

        // Cari ONU by serial number (case-insensitive karena beberapa firmware
        // kirim uppercase, beberapa mixed-case)
        $onu = Onu::with('customer')
            ->whereRaw('UPPER(serial_number) = ?', [strtoupper($serial)])
            ->first();

        if (!$onu) {
            return response()->json(['found' => false]);
        }

        // Ambil PPPoE credentials: prioritas dari customer, fallback ke onu.pppoe_username
        $customer = $onu->customer;

        $pppoeUsername = $customer?->pppoe_username ?? $onu->pppoe_username ?? null;

        // pppoe_password: prioritas dari customer, fallback ke onus.pppoe_password
        // Ini memungkinkan ONU standalone (tanpa customer) tetap bisa di-provision.
        $pppoePassword = null;
        if ($customer && $customer->pppoe_password) {
            try {
                $pppoePassword = $customer->decrypted_pppoe_password;
            } catch (\Exception $e) {
                // Decryption failed — jangan expose error detail
                $pppoePassword = null;
            }
        }
        if ($pppoePassword === null && $onu->pppoe_password) {
            try {
                $pppoePassword = $onu->pppoe_password; // cast 'encrypted' auto-decrypt
            } catch (\Exception $e) {
                $pppoePassword = null;
            }
        }

        // VLAN dari vlan_config JSON
        $vlanConfig  = $onu->vlan_config ?? [];
        $vlan        = isset($vlanConfig['vlan_id']) ? (int) $vlanConfig['vlan_id'] : null;

        // WiFi config (nullable — push hanya jika tersedia)
        $wifiSsid = $onu->wifi_ssid ?? null;
        $wifiPassword = null;
        if ($onu->wifi_password) {
            try {
                $wifiPassword = $onu->wifi_password; // already decrypted by cast
            } catch (\Exception $e) {
                $wifiPassword = null;
            }
        }

        // Jangan return data jika tidak ada PPPoE credentials sama sekali
        if (!$pppoeUsername || !$vlan) {
            return response()->json([
                'found'    => true,
                'has_data' => false,
                'reason'   => 'PPPoE credentials or VLAN not configured in billing system.',
            ]);
        }

        return response()->json([
            'found'          => true,
            'has_data'       => true,
            'pppoe_username' => $pppoeUsername,
            'pppoe_password' => $pppoePassword ?? '',
            'vlan'           => $vlan,
            'wifi_ssid'      => $wifiSsid,
            'wifi_password'  => $wifiPassword ?? '',
        ]);
    }
}
