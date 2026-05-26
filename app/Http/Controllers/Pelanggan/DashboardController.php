<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Services\CustomerConnectivityService;
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
            $daysUntilDue = (int) now()->diffInDays($customer->active_until, false);
        }

        // Current billing period
        $billingDay = $customer->billing_day ?? 1;
        $now = now();
        $periodStart = $now->copy()->day($billingDay);
        if ($now->day < $billingDay) {
            $periodStart->subMonth();
        }
        $periodEnd = $periodStart->copy()->addMonth()->subDay();
        $billingPeriod = [
            'month' => $periodStart->translatedFormat('F Y'),
            'start' => $periodStart->format('d M Y'),
            'end' => $periodEnd->format('d M Y'),
            'day_of_period' => $periodStart->diffInDays($now) + 1,
            'total_days' => $periodStart->diffInDays($periodEnd) + 1,
        ];

        // Payment summary
        $totalPaid = $customer->payments()->where('status', 'success')->sum('amount');
        $totalUnpaid = $customer->invoices()->whereIn('status', ['pending', 'overdue'])->sum('total_amount');
        
        return view('pelanggan.dashboard', compact(
            'customer',
            'pendingInvoices',
            'recentPayments',
            'connectionStatus',
            'daysUntilDue',
            'billingPeriod',
            'totalPaid',
            'totalUnpaid'
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
            $color = match($customer->status) {
                'suspended' => 'warning',
                'pending' => 'info',
                default => 'danger',
            };
            return [
                'online' => false,
                'status' => $customer->status_label,
                'color' => $color,
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
    public function connection(CustomerConnectivityService $connectivity)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return redirect()->route('pelanggan.dashboard');
        }
        
        $customer->load(['router', 'package', 'onu']);
        $connectionStatus = $connectivity->summary($customer, true);

        // Billing period
        $billingDay = $customer->billing_day ?? 1;
        $now = now();
        $periodStart = $now->copy()->day($billingDay);
        if ($now->day < $billingDay) {
            $periodStart->subMonth();
        }
        $periodEnd = $periodStart->copy()->addMonth()->subDay();
        $billingPeriod = [
            'month' => $periodStart->translatedFormat('F Y'),
            'start' => $periodStart->format('d M Y'),
            'end' => $periodEnd->format('d M Y'),
            'day_of_period' => $periodStart->diffInDays($now) + 1,
            'total_days' => $periodStart->diffInDays($periodEnd) + 1,
        ];

        // Latest invoice & payment
        $latestInvoice = $customer->invoices()->latest('invoice_date')->first();
        $latestPayment = $customer->payments()->where('status', 'success')->latest('paid_at')->first();
        $pendingInvoiceCount = $customer->invoices()->whereIn('status', ['pending', 'overdue'])->count();
        
        return view('pelanggan.connection', compact('customer', 'billingPeriod', 'latestInvoice', 'latestPayment', 'pendingInvoiceCount', 'connectionStatus'));
    }

    public function wifi()
    {
        $customer = Auth::user()->customerProfile;
        if (!$customer) {
            return redirect()->route('pelanggan.dashboard');
        }

        $status = app(CustomerConnectivityService::class)->summary($customer, true);

        return view('pelanggan.wifi', compact('customer', 'status'));
    }

    public function updateWifi(Request $request, CustomerConnectivityService $connectivity)
    {
        $customer = Auth::user()->customerProfile;
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'wlan_path' => 'required|string|max:500',
            'ssid' => 'required|string|min:1|max:32',
            'password' => 'required|string|min:8|max:63',
        ]);

        return response()->json($connectivity->updateWifi($customer, $validated, true));
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
