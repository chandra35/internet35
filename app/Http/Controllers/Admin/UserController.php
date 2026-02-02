<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.view', only: ['index', 'show', 'getData']),
            new Middleware('permission:users.create', only: ['create', 'store']),
            new Middleware('permission:users.edit', only: ['edit', 'update']),
            new Middleware('permission:users.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get DataTable data via separate endpoint
     */
    public function getData(Request $request)
    {
        return $this->getDataTable($request);
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        // Check if this is a DataTable request
        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            try {
                return $this->getDataTable($request);
            } catch (\Exception $e) {
                \Log::error('DataTable error: ' . $e->getMessage());
                return response()->json([
                    'draw' => intval($request->input('draw', 1)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $roles = Role::all();
        $users = User::with('roles')->orderBy('created_at', 'desc')->get();
        return view('admin.users.index', compact('roles', 'users'));
    }

    /**
     * Get DataTable data
     */
    protected function getDataTable(Request $request)
    {
        $query = User::with('roles');

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $totalRecords = User::count();
        $filteredRecords = $query->count();

        // Sorting - map column index to column name
        $orderColumn = (int) $request->input('order.0.column', 5);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = [
            0 => 'name',
            1 => 'email', 
            2 => 'phone',
            3 => 'created_at', // roles - not sortable, fallback to created_at
            4 => 'is_active',
            5 => 'created_at',
        ];
        
        $sortColumn = $columns[$orderColumn] ?? 'created_at';
        $query->orderBy($sortColumn, $orderDir);

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $users = $query->skip($start)->take($length)->get();

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'avatar' => $user->avatar_url,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '-',
                'roles' => $user->roles->pluck('name')->map(function ($role) {
                    $colors = [
                        'superadmin' => 'danger',
                        'admin' => 'primary',
                        'admin-pop' => 'info',
                        'client' => 'success',
                    ];
                    $color = $colors[$role] ?? 'secondary';
                    return "<span class='badge badge-{$color}'>{$role}</span>";
                })->implode(' '),
                'is_active' => $user->is_active
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-danger">Nonaktif</span>',
                'created_at' => $user->created_at->format('d/m/Y H:i'),
                'actions' => $this->getActionButtons($user),
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
    protected function getActionButtons($user)
    {
        $buttons = '';
        
        // View password button (only for superadmin)
        if (auth()->user()->hasRole('superadmin')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-warning btn-password" data-id="' . $user->id . '" data-name="' . $user->name . '" title="Lihat Password"><i class="fas fa-key"></i></button> ';
        }
        
        if (auth()->user()->can('users.edit')) {
            $buttons .= '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $user->id . '" title="Edit"><i class="fas fa-edit"></i></button> ';
        }
        
        // Cannot delete own account or superadmin
        $isSuperadmin = $user->hasRole('superadmin');
        if (auth()->user()->can('users.delete') && $user->id !== auth()->id() && !$isSuperadmin) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $user->id . '" data-name="' . $user->name . '" title="Hapus"><i class="fas fa-trash"></i></button>';
        }

        return $buttons;
    }

    /**
     * Show create form
     */
    public function create()
    {
        $roles = Role::all();
        return response()->json([
            'success' => true,
            'html' => view('admin.users._form', ['user' => null, 'roles' => $roles])->render(),
        ]);
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        // Check for soft deleted user with same email
        $existingUser = User::withTrashed()
            ->where('email', $request->email)
            ->first();
            
        if ($existingUser) {
            if ($existingUser->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ini pernah digunakan oleh user yang sudah dihapus. Silakan restore user tersebut atau gunakan email lain!',
                    'errors' => ['email' => ['Email pernah digunakan oleh user yang sudah dihapus']]
                ], 422);
            }
            return response()->json([
                'success' => false,
                'message' => 'Email sudah digunakan!',
                'errors' => ['email' => ['Email sudah digunakan']]
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'plain_password' => $request->password, // Store encrypted plain password
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $user->syncRoles($request->roles);

        $this->activityLog->logCreate('users', "Created user: {$user->name}", $user->toArray());

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan!',
        ]);
    }

    /**
     * Show user detail
     */
    public function show(User $user)
    {
        $user->load('roles');
        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Get user password (admin only)
     */
    public function getPassword(User $user)
    {
        // Only superadmin can view passwords
        if (!auth()->user()->hasRole('superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat password!',
            ], 403);
        }

        $password = $user->decrypted_password;

        if (!$password) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak tersedia untuk user ini.',
            ]);
        }

        // Log this sensitive action
        $this->activityLog->log('view_password', 'users', "Viewed password for user: {$user->name}");

        return response()->json([
            'success' => true,
            'password' => $password,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::all();
        return response()->json([
            'success' => true,
            'html' => view('admin.users._form', ['user' => $user, 'roles' => $roles])->render(),
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        // Check for soft deleted user with same email (exclude current user)
        if ($request->email !== $user->email) {
            $existingUser = User::withTrashed()
                ->where('email', $request->email)
                ->where('id', '!=', $user->id)
                ->first();
                
            if ($existingUser) {
                if ($existingUser->trashed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email ini pernah digunakan oleh user yang sudah dihapus!',
                        'errors' => ['email' => ['Email pernah digunakan oleh user yang sudah dihapus']]
                    ], 422);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah digunakan!',
                    'errors' => ['email' => ['Email sudah digunakan']]
                ], 422);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'is_active' => 'boolean',
        ]);

        // Protect superadmin - cannot remove superadmin role
        if ($user->hasRole('superadmin')) {
            $newRoles = $request->roles;
            if (!in_array('superadmin', $newRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role superadmin tidak dapat dihapus dari user ini!',
                ], 403);
            }
        }

        // Cannot deactivate superadmin
        if ($user->hasRole('superadmin') && !$request->boolean('is_active', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Superadmin tidak dapat dinonaktifkan!',
            ], 403);
        }

        $oldData = $user->toArray();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
                'plain_password' => $request->password, // Update encrypted plain password
            ]);
        }

        $user->syncRoles($request->roles);

        $this->activityLog->logUpdate('users', "Updated user: {$user->name}", $oldData, $user->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diupdate!',
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        // Cannot delete own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun sendiri!',
            ], 403);
        }

        // Cannot delete superadmin
        if ($user->hasRole('superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Superadmin tidak dapat dihapus!',
            ], 403);
        }

        $oldData = $user->toArray();
        $userName = $user->name;

        $user->delete();

        $this->activityLog->logDelete('users', "Deleted user: {$userName}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus!',
        ]);
    }

    /**
     * Check if user can be modified
     */
    protected function canModifyUser(User $user): bool
    {
        // Cannot modify own account role to non-superadmin if you are superadmin
        if ($user->id === auth()->id() && auth()->user()->hasRole('superadmin')) {
            return true;
        }

        return true;
    }
}
