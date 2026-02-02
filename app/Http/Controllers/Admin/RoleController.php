<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index', 'show']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.edit', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTable($request);
        }

        return view('admin.roles.index');
    }

    /**
     * Get DataTable data
     */
    protected function getDataTable(Request $request)
    {
        $query = Role::withCount(['permissions', 'users']);

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalRecords = Role::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['name', 'description', 'permissions_count', 'users_count', 'created_at'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $roles = $query->skip($start)->take($length)->get();

        $data = $roles->map(function ($role) {
            $colors = [
                'superadmin' => 'danger',
                'admin' => 'primary',
                'admin-pop' => 'info',
                'client' => 'success',
            ];
            $color = $colors[$role->name] ?? 'secondary';

            return [
                'id' => $role->id,
                'name' => "<span class='badge badge-{$color}'>{$role->name}</span>",
                'description' => $role->description ?? '-',
                'permissions_count' => "<span class='badge badge-info'>{$role->permissions_count}</span>",
                'users_count' => "<span class='badge badge-primary'>{$role->users_count}</span>",
                'created_at' => $role->created_at->format('d/m/Y H:i'),
                'actions' => $this->getActionButtons($role),
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
    protected function getActionButtons($role)
    {
        $buttons = '';
        
        if (auth()->user()->can('roles.view')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-secondary btn-view" data-id="' . $role->id . '" title="Lihat"><i class="fas fa-eye"></i></button> ';
        }
        
        if (auth()->user()->can('roles.edit')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $role->id . '" title="Edit"><i class="fas fa-edit"></i></button> ';
        }
        
        // Protect default roles from deletion
        $protectedRoles = ['superadmin', 'admin', 'admin-pop', 'client'];
        if (auth()->user()->can('roles.delete') && !in_array($role->name, $protectedRoles)) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $role->id . '" data-name="' . $role->name . '" title="Hapus"><i class="fas fa-trash"></i></button>';
        }

        return $buttons;
    }

    /**
     * Show create form
     */
    public function create()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        return response()->json([
            'success' => true,
            'html' => view('admin.roles._form', ['role' => null, 'permissions' => $permissions])->render(),
        ]);
    }

    /**
     * Store a new role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => strtolower(str_replace(' ', '-', $request->name)),
            'guard_name' => 'web',
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $this->activityLog->logCreate('roles', "Created role: {$role->name}", $role->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil ditambahkan!',
        ]);
    }

    /**
     * Show role detail
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);
        $role->loadCount(['permissions', 'users']);
        
        $permissionsByGroup = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        
        return response()->json([
            'success' => true,
            'html' => view('admin.roles._show', [
                'role' => $role,
                'permissionsByGroup' => $permissionsByGroup,
            ])->render(),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        
        return response()->json([
            'success' => true,
            'html' => view('admin.roles._form', [
                'role' => $role,
                'permissions' => $permissions,
                'rolePermissions' => $rolePermissions,
            ])->render(),
        ]);
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
        ]);

        $oldData = $role->toArray();

        $role->update([
            'description' => $request->description,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        $this->activityLog->logUpdate('roles', "Updated role: {$role->name}", $oldData, $role->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diupdate!',
        ]);
    }

    /**
     * Delete role
     */
    public function destroy(Role $role)
    {
        $protectedRoles = ['superadmin', 'admin', 'admin-pop', 'client'];
        
        if (in_array($role->name, $protectedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Role bawaan tidak dapat dihapus!',
            ], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Role masih digunakan oleh user!',
            ], 403);
        }

        $oldData = $role->toArray();
        $roleName = $role->name;

        $role->delete();

        $this->activityLog->logDelete('roles', "Deleted role: {$roleName}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus!',
        ]);
    }
}
