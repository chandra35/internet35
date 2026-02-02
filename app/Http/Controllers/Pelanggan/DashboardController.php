<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show customer dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Akun Anda tidak terhubung dengan data pelanggan.');
        }
        
        // Load relationships
        $customer->load(['router', 'package', 'province', 'city', 'district', 'village']);
        
        // Get pending invoices
        $pendingInvoices = CustomerInvoice::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();
        
        // Get recent payments
        $recentPayments = $customer->payments()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Connection status
        $connectionStatus = $this->checkConnectionStatus($customer);
        
        // Calculate days until due
        $daysUntilDue = null;
        if ($customer->active_until) {
            $daysUntilDue = now()->diffInDays($customer->active_until, false);
        }
        
        return view('pelanggan.dashboard', compact(
            'customer',
            'pendingInvoices',
            'recentPayments',
            'connectionStatus',
            'daysUntilDue'
        ));
    }

    /**
     * Check connection status (simplified - will integrate with Mikrotik later)
     */
    protected function checkConnectionStatus(Customer $customer): array
    {
        // Basic status based on customer data
        // TODO: Integrate with Mikrotik API for real-time status
        
        if ($customer->status !== 'active') {
            return [
                'online' => false,
                'status' => $customer->status_label,
                'color' => 'danger',
            ];
        }
        
        if ($customer->active_until && $customer->active_until->isPast()) {
            return [
                'online' => false,
                'status' => 'Masa aktif habis',
                'color' => 'warning',
            ];
        }
        
        if ($customer->mikrotik_status === 'disabled') {
            return [
                'online' => false,
                'status' => 'Dinonaktifkan',
                'color' => 'danger',
            ];
        }
        
        return [
            'online' => true,
            'status' => 'Aktif',
            'color' => 'success',
        ];
    }

    /**
     * Show connection info
     */
    public function connection()
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return redirect()->route('pelanggan.dashboard');
        }
        
        $customer->load(['router', 'package']);
        
        return view('pelanggan.connection', compact('customer'));
    }

    /**
     * Get PPPoE credentials
     */
    public function credentials()
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return response()->json(['error' => 'Tidak ditemukan'], 404);
        }
        
        return response()->json([
            'username' => $customer->pppoe_username,
            'password' => $customer->decrypted_pppoe_password,
        ]);
    }
}
