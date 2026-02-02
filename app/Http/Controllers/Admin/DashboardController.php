<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $totalRoles = Role::count();
        $todayLogins = ActivityLog::where('action', 'login')
            ->whereDate('created_at', today())
            ->count();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        $usersByRole = Role::withCount('users')->get();

        // Activity chart data (last 7 days)
        $activityChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $activityChart[] = [
                'date' => $date->format('d M'),
                'count' => ActivityLog::whereDate('created_at', $date)->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'totalRoles',
            'todayLogins',
            'recentActivities',
            'usersByRole',
            'activityChart'
        ));
    }
}
