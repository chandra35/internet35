<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:activity-logs.view', only: ['index', 'show']),
            new Middleware('permission:activity-logs.delete', only: ['destroy', 'bulkDelete']),
        ];
    }

    /**
     * Display a listing of activity logs
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTable($request);
        }

        $actions = ActivityLog::distinct()->pluck('action');
        $modules = ActivityLog::distinct()->pluck('module')->filter();

        return view('admin.activity-logs.index', compact('actions', 'modules'));
    }

    /**
     * Get DataTable data
     */
    protected function getDataTable(Request $request)
    {
        $query = ActivityLog::with('user');

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filters
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $totalRecords = ActivityLog::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->input('order.0.column', 5);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['user_id', 'action', 'module', 'ip_address', 'browser', 'created_at'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $logs = $query->skip($start)->take($length)->get();

        $data = $logs->map(function ($log) {
            $actionColors = [
                'login' => 'success',
                'logout' => 'secondary',
                'login_failed' => 'danger',
                'create' => 'primary',
                'update' => 'info',
                'delete' => 'danger',
                'view' => 'light',
            ];
            $color = $actionColors[$log->action] ?? 'secondary';

            return [
                'id' => $log->id,
                'user' => $log->user ? $log->user->name : '<em>System</em>',
                'action' => "<span class='badge badge-{$color}'>{$log->action}</span>",
                'module' => $log->module ? "<span class='badge badge-outline-secondary'>{$log->module}</span>" : '-',
                'description' => Str::limit($log->description, 50),
                'ip_address' => $log->ip_address,
                'location' => $log->city ? "{$log->city}, {$log->country_code}" : '-',
                'browser' => $log->browser ? "{$log->browser}" : '-',
                'os' => $log->os ?? '-',
                'device' => $log->device_type ?? '-',
                'created_at' => $log->created_at->format('d/m/Y H:i:s'),
                'actions' => $this->getActionButtons($log),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get action buttons HTML
     */
    protected function getActionButtons($log)
    {
        $buttons = '<button type="button" class="btn btn-sm btn-primary btn-view" data-id="' . $log->id . '" title="Detail"><i class="fas fa-eye"></i></button> ';
        
        if (auth()->user()->can('activity-logs.delete')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $log->id . '" title="Hapus"><i class="fas fa-trash"></i></button>';
        }

        return $buttons;
    }

    /**
     * Show activity log detail
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user');
        
        return response()->json([
            'success' => true,
            'html' => view('admin.activity-logs._detail', ['log' => $activityLog])->render(),
        ]);
    }

    /**
     * Delete activity log
     */
    public function destroy(ActivityLog $activityLog)
    {
        $activityLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity log berhasil dihapus!',
        ]);
    }

    /**
     * Bulk delete old logs
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $date = now()->subDays($request->days);
        $count = ActivityLog::where('created_at', '<', $date)->count();
        
        ActivityLog::where('created_at', '<', $date)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} log lebih dari {$request->days} hari berhasil dihapus!",
        ]);
    }
}
