<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StaffController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:staff.view', only: ['index', 'show']),
            new Middleware('permission:staff.create', only: ['create', 'store']),
            new Middleware('permission:staff.edit', only: ['edit', 'update']),
            new Middleware('permission:staff.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get staff roles that can be managed by admin-pop
     */
    protected function getStaffRoles(): array
    {
        return ['teknisi', 'support'];
    }

    /**
     * Display a listing of staff members
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get staff users belonging to this admin-pop
        $query = User::with('roles')
            ->where('parent_id', $user->id)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', $this->getStaffRoles());
            });

        // Search
        if ($search = $request->input('search')) {
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

        $staff = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::whereIn('name', $this->getStaffRoles())->get();

        return view('admin.staff.index', compact('staff', 'roles'));
    }

    /**
     * Show the form for creating a new staff member
     */
    public function create()
    {
        $roles = Role::whereIn('name', $this->getStaffRoles())->get();
        return view('admin.staff.create', compact('roles'));
    }

    /**
     * Store a newly created staff member
     */
    public function store(Request $request)
    {
        // Check for soft deleted user with same email
        $existingUser = User::withTrashed()
            ->where('email', $request->email)
            ->first();
            
        if ($existingUser) {
            if ($existingUser->trashed()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['email' => 'Email ini pernah digunakan oleh user yang sudah dihapus. Silakan restore user tersebut atau gunakan email lain!']);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'Email sudah digunakan!']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in($this->getStaffRoles())],
        ]);

        $user = auth()->user();

        $staff = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'parent_id' => $user->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staff->assignRole($request->role);

        $this->activityLog->logCreate('staff', "Created staff: {$staff->name} ({$request->role})", $staff->toArray());

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff berhasil ditambahkan!');
    }

    /**
     * Display the specified staff member
     */
    public function show(User $staff)
    {
        $this->authorizeStaff($staff);
        
        return view('admin.staff.show', compact('staff'));
    }

    /**
     * Show the form for editing the specified staff member
     */
    public function edit(User $staff)
    {
        $this->authorizeStaff($staff);
        
        $roles = Role::whereIn('name', $this->getStaffRoles())->get();
        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    /**
     * Update the specified staff member
     */
    public function update(Request $request, User $staff)
    {
        $this->authorizeStaff($staff);

        // Check for soft deleted user with same email (exclude current staff)
        if ($request->email !== $staff->email) {
            $existingUser = User::withTrashed()
                ->where('email', $request->email)
                ->where('id', '!=', $staff->id)
                ->first();
                
            if ($existingUser) {
                if ($existingUser->trashed()) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['email' => 'Email ini pernah digunakan oleh user yang sudah dihapus!']);
                }
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['email' => 'Email sudah digunakan!']);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in($this->getStaffRoles())],
            'is_active' => 'boolean',
        ]);

        $oldData = $staff->toArray();

        $staff->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('password')) {
            $staff->update(['password' => Hash::make($request->password)]);
        }

        // Update role
        $staff->syncRoles([$request->role]);

        $this->activityLog->logUpdate('staff', "Updated staff: {$staff->name}", $oldData, $staff->fresh()->toArray());

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff berhasil diperbarui!');
    }

    /**
     * Remove the specified staff member
     */
    public function destroy(User $staff)
    {
        $this->authorizeStaff($staff);

        $staffName = $staff->name;
        $oldData = $staff->toArray();

        $staff->delete();

        $this->activityLog->logDelete('staff', "Deleted staff: {$staffName}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Staff berhasil dihapus!'
        ]);
    }

    /**
     * Toggle staff active status
     */
    public function toggleStatus(User $staff)
    {
        $this->authorizeStaff($staff);

        $staff->update(['is_active' => !$staff->is_active]);

        $status = $staff->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return response()->json([
            'success' => true,
            'message' => "Staff berhasil {$status}!",
            'is_active' => $staff->is_active
        ]);
    }

    /**
     * Authorize that the staff belongs to current admin-pop
     */
    protected function authorizeStaff(User $staff): void
    {
        $user = auth()->user();
        
        if ($staff->parent_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke staff ini.');
        }

        if (!$staff->hasAnyRole($this->getStaffRoles())) {
            abort(403, 'User ini bukan staff.');
        }
    }
}
