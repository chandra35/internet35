<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:dashboard.view'),
        ];
    }

    public function index()
    {
        $user = auth()->user();

        // POP scoping: superadmin sees all, admin-pop sees own POP
        $popId = $user->hasRole('superadmin') ? null : $user->id;

        // ── Customer stats ───────────────────────────────────────────
        $custQ = Customer::when($popId, fn($q) => $q->where('pop_id', $popId));
        $totalCustomers     = (clone $custQ)->count();
        $activeCustomers    = (clone $custQ)->where('status', 'active')->count();
        $suspendedCustomers = (clone $custQ)->where('status', 'suspended')->count();
        $expectedRevenue    = (clone $custQ)->where('status', 'active')->sum('monthly_fee');

        // ── Invoice stats ────────────────────────────────────────────
        $invQ = CustomerInvoice::whereHas(
            'customer',
            fn($q) => $q->when($popId, fn($q2) => $q2->where('pop_id', $popId))
        );
        $pendingInvoicesCount  = (clone $invQ)->where('status', 'pending')->count();
        $pendingInvoicesAmount = (clone $invQ)->where('status', 'pending')->sum('total_amount');
        $paidThisMonthCount    = (clone $invQ)->where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();
        $paidThisMonthAmount   = (clone $invQ)->where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('paid_amount');

        // ── Invoice recap this month (for sidebar panel) ─────────────
        $monthInvQ = (clone $invQ)
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year);
        $invoiceRecap = [
            'total_count'    => (clone $monthInvQ)->count(),
            'total_amount'   => (clone $monthInvQ)->sum('total_amount'),
            'paid_count'     => (clone $monthInvQ)->where('status', 'paid')->count(),
            'paid_amount'    => (clone $monthInvQ)->where('status', 'paid')->sum('paid_amount'),
            'pending_count'  => (clone $monthInvQ)->where('status', 'pending')->count(),
            'pending_amount' => (clone $monthInvQ)->where('status', 'pending')->sum('total_amount'),
            'overdue_count'  => (clone $monthInvQ)->where('status', 'overdue')->count(),
            'overdue_amount' => (clone $monthInvQ)->where('status', 'overdue')->sum('total_amount'),
        ];

        // ── Activity / roles (unchanged) ─────────────────────────────
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $totalRoles = Role::count();
        $todayLogins = ActivityLog::where('action', 'login')
            ->whereDate('created_at', today())
            ->count();

        $recentActivities = ActivityLog::with('user')
            ->when($popId, fn($q) => $q->where('user_id', $popId))
            ->latest()
            ->take(15)
            ->get();

        $usersByRole = Role::withCount('users')->get();

        // Activity chart data (last 7 days) — scoped to POP
        $activityChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $activityChart[] = [
                'date'  => $date->format('d M'),
                'count' => ActivityLog::when($popId, fn($q) => $q->where('user_id', $popId))
                    ->whereDate('created_at', $date)->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'totalUsers', 'activeUsers', 'totalRoles', 'todayLogins',
            'recentActivities', 'usersByRole', 'activityChart',
            'totalCustomers', 'activeCustomers', 'suspendedCustomers', 'expectedRevenue',
            'pendingInvoicesCount', 'pendingInvoicesAmount',
            'paidThisMonthCount', 'paidThisMonthAmount', 'invoiceRecap'
        ));
    }
}
