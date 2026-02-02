<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\ActivityLogService;
use App\Services\PermissionScannerService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:permissions.view', only: ['index', 'show']),
            new Middleware('permission:permissions.create', only: ['create', 'store']),
            new Middleware('permission:permissions.edit', only: ['edit', 'update']),
            new Middleware('permission:permissions.delete', only: ['destroy']),
            new Middleware('permission:permissions.scan', only: ['scan']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Display a listing of permissions
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTable($request);
        }

        $groups = Permission::distinct()->pluck('group')->filter()->sort()->values();
        
        // Get permissions grouped by their group name
        $permissionsByGroup = Permission::with('roles')
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy(function($permission) {
                return $permission->group ?: '';
            });
        
        // Statistics
        $totalPermissions = Permission::count();
        $totalGroups = Permission::distinct()->whereNotNull('group')->where('group', '!=', '')->count('group');
        $ungroupedCount = Permission::whereNull('group')->orWhere('group', '')->count();
        $totalRoles = \App\Models\Role::count();
        
        return view('admin.permissions.index', compact(
            'groups', 
            'permissionsByGroup', 
            'totalPermissions', 
            'totalGroups', 
            'ungroupedCount',
            'totalRoles'
        ));
    }

    /**
     * Get DataTable data
     */
    protected function getDataTable(Request $request)
    {
        $query = Permission::query();

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%");
            });
        }

        // Group filter
        if ($group = $request->input('group')) {
            $query->where('group', $group);
        }

        $totalRecords = Permission::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['name', 'group', 'description', 'created_at'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $permissions = $query->skip($start)->take($length)->get();

        $data = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => "<code>{$permission->name}</code>",
                'group' => $permission->group ? "<span class='badge badge-secondary'>{$permission->group}</span>" : '-',
                'description' => $permission->description ?? '-',
                'created_at' => $permission->created_at->format('d/m/Y H:i'),
                'actions' => $this->getActionButtons($permission),
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
    protected function getActionButtons($permission)
    {
        $buttons = '';
        
        if (auth()->user()->can('permissions.edit')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $permission->id . '" title="Edit"><i class="fas fa-edit"></i></button> ';
        }
        
        if (auth()->user()->can('permissions.delete')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $permission->id . '" data-name="' . $permission->name . '" title="Hapus"><i class="fas fa-trash"></i></button>';
        }

        return $buttons;
    }

    /**
     * Scan and sync permissions
     */
    public function scan()
    {
        $scanner = new PermissionScannerService();
        $result = $scanner->syncToDatabase();

        $this->activityLog->log('scan', 'permissions', 'Scanned and synced permissions', null, $result);

        return response()->json([
            'success' => true,
            'message' => 'Permissions berhasil di-scan!',
            'created' => count($result['created']),
            'existing' => count($result['existing']),
            'created_permissions' => $result['created'],
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $groups = Permission::distinct()->pluck('group')->filter()->sort();
        return response()->json([
            'success' => true,
            'html' => view('admin.permissions._form', ['permission' => null, 'groups' => $groups])->render(),
        ]);
    }

    /**
     * Store a new permission
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = Permission::create([
            'name' => strtolower(str_replace(' ', '.', $request->name)),
            'guard_name' => 'web',
            'group' => $request->group,
            'description' => $request->description,
        ]);

        $this->activityLog->logCreate('permissions', "Created permission: {$permission->name}", $permission->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil ditambahkan!',
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Permission $permission)
    {
        $groups = Permission::distinct()->pluck('group')->filter()->sort();
        return response()->json([
            'success' => true,
            'html' => view('admin.permissions._form', ['permission' => $permission, 'groups' => $groups])->render(),
        ]);
    }

    /**
     * Update permission
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $oldData = $permission->toArray();

        $permission->update([
            'group' => $request->group,
            'description' => $request->description,
        ]);

        $this->activityLog->logUpdate('permissions', "Updated permission: {$permission->name}", $oldData, $permission->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil diupdate!',
        ]);
    }

    /**
     * Delete permission
     */
    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Permission masih digunakan oleh role!',
            ], 403);
        }

        $oldData = $permission->toArray();
        $permissionName = $permission->name;

        $permission->delete();

        $this->activityLog->logDelete('permissions', "Deleted permission: {$permissionName}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil dihapus!',
        ]);
    }

    /**
     * Update permission group (for drag & drop)
     */
    public function updateGroup(Request $request, Permission $permission)
    {
        $request->validate([
            'group' => 'nullable|string|max:100',
        ]);

        $oldGroup = $permission->group;
        $newGroup = $request->group ?: null;

        $permission->update([
            'group' => $newGroup,
        ]);

        $this->activityLog->log(
            'update', 
            'permissions', 
            "Moved permission {$permission->name} from group '{$oldGroup}' to '{$newGroup}'",
            $permission->id,
            ['old_group' => $oldGroup, 'new_group' => $newGroup]
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission group berhasil diupdate!',
        ]);
    }
}
