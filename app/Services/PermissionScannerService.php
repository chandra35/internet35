<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PermissionScannerService
{
    /**
     * Scan and register all permissions from routes
     */
    public function scan(): array
    {
        $permissions = $this->getDefaultPermissions();
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();
            
            if (!$name || $this->shouldSkipRoute($name)) {
                continue;
            }

            $permission = $this->routeNameToPermission($name);
            if ($permission) {
                $group = $this->getPermissionGroup($name);
                $permissions[$group][] = $permission;
            }
        }

        return $permissions;
    }

    /**
     * Sync permissions to database
     */
    public function syncToDatabase(): array
    {
        $permissions = $this->scan();
        $created = [];
        $existing = [];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                $permissionName = is_array($perm) ? $perm['name'] : $perm;
                $description = is_array($perm) ? ($perm['description'] ?? null) : null;

                $exists = Permission::where('name', $permissionName)->first();
                
                if (!$exists) {
                    Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                        'group' => $group,
                        'description' => $description,
                    ]);
                    $created[] = $permissionName;
                } else {
                    $existing[] = $permissionName;
                }
            }
        }

        return [
            'created' => $created,
            'existing' => $existing,
        ];
    }

    /**
     * Get default permissions
     */
    protected function getDefaultPermissions(): array
    {
        return [
            'dashboard' => [
                ['name' => 'dashboard.view', 'description' => 'View dashboard'],
            ],
            'users' => [
                ['name' => 'users.view', 'description' => 'View users list'],
                ['name' => 'users.create', 'description' => 'Create new user'],
                ['name' => 'users.edit', 'description' => 'Edit user'],
                ['name' => 'users.delete', 'description' => 'Delete user'],
                ['name' => 'users.export', 'description' => 'Export users data'],
            ],
            'roles' => [
                ['name' => 'roles.view', 'description' => 'View roles list'],
                ['name' => 'roles.create', 'description' => 'Create new role'],
                ['name' => 'roles.edit', 'description' => 'Edit role'],
                ['name' => 'roles.delete', 'description' => 'Delete role'],
            ],
            'permissions' => [
                ['name' => 'permissions.view', 'description' => 'View permissions list'],
                ['name' => 'permissions.create', 'description' => 'Create new permission'],
                ['name' => 'permissions.edit', 'description' => 'Edit permission'],
                ['name' => 'permissions.delete', 'description' => 'Delete permission'],
                ['name' => 'permissions.scan', 'description' => 'Scan and sync permissions'],
            ],
            'activity-logs' => [
                ['name' => 'activity-logs.view', 'description' => 'View activity logs'],
                ['name' => 'activity-logs.delete', 'description' => 'Delete activity logs'],
                ['name' => 'activity-logs.export', 'description' => 'Export activity logs'],
            ],
            'profile' => [
                ['name' => 'profile.view', 'description' => 'View own profile'],
                ['name' => 'profile.edit', 'description' => 'Edit own profile'],
            ],
            'landing' => [
                ['name' => 'landing.contents.view', 'description' => 'View landing contents'],
                ['name' => 'landing.contents.create', 'description' => 'Create landing content'],
                ['name' => 'landing.contents.edit', 'description' => 'Edit landing contents'],
                ['name' => 'landing.contents.delete', 'description' => 'Delete landing content'],
                ['name' => 'landing.sliders.view', 'description' => 'View landing sliders'],
                ['name' => 'landing.sliders.create', 'description' => 'Create landing slider'],
                ['name' => 'landing.sliders.edit', 'description' => 'Edit landing slider'],
                ['name' => 'landing.sliders.delete', 'description' => 'Delete landing slider'],
                ['name' => 'landing.services.view', 'description' => 'View landing services'],
                ['name' => 'landing.services.create', 'description' => 'Create landing service'],
                ['name' => 'landing.services.edit', 'description' => 'Edit landing service'],
                ['name' => 'landing.services.delete', 'description' => 'Delete landing service'],
                ['name' => 'landing.testimonials.view', 'description' => 'View testimonials'],
                ['name' => 'landing.testimonials.create', 'description' => 'Create testimonial'],
                ['name' => 'landing.testimonials.edit', 'description' => 'Edit testimonial'],
                ['name' => 'landing.testimonials.delete', 'description' => 'Delete testimonial'],
                ['name' => 'landing.faqs.view', 'description' => 'View FAQs'],
                ['name' => 'landing.faqs.create', 'description' => 'Create FAQ'],
                ['name' => 'landing.faqs.edit', 'description' => 'Edit FAQ'],
                ['name' => 'landing.faqs.delete', 'description' => 'Delete FAQ'],
                ['name' => 'landing.packages.view', 'description' => 'View packages'],
                ['name' => 'landing.packages.create', 'description' => 'Create package'],
                ['name' => 'landing.packages.edit', 'description' => 'Edit package'],
                ['name' => 'landing.packages.delete', 'description' => 'Delete package'],
            ],
            'settings' => [
                ['name' => 'settings.view', 'description' => 'View site settings'],
                ['name' => 'settings.edit', 'description' => 'Edit site settings'],
            ],
            'routers' => [
                ['name' => 'routers.view', 'description' => 'View routers list'],
                ['name' => 'routers.create', 'description' => 'Create new router'],
                ['name' => 'routers.edit', 'description' => 'Edit router'],
                ['name' => 'routers.delete', 'description' => 'Delete router'],
                ['name' => 'routers.manage', 'description' => 'Manage router (Winbox-like)'],
            ],
            'staff' => [
                ['name' => 'staff.view', 'description' => 'View staff list'],
                ['name' => 'staff.create', 'description' => 'Create new staff'],
                ['name' => 'staff.edit', 'description' => 'Edit staff'],
                ['name' => 'staff.delete', 'description' => 'Delete staff'],
            ],
            'packages' => [
                ['name' => 'packages.view', 'description' => 'View packages list'],
                ['name' => 'packages.create', 'description' => 'Create new package'],
                ['name' => 'packages.edit', 'description' => 'Edit package'],
                ['name' => 'packages.delete', 'description' => 'Delete package'],
                ['name' => 'packages.sync', 'description' => 'Sync packages with router'],
            ],
            'customers' => [
                ['name' => 'customers.view', 'description' => 'View customers list'],
                ['name' => 'customers.create', 'description' => 'Create new customer'],
                ['name' => 'customers.edit', 'description' => 'Edit customer'],
                ['name' => 'customers.delete', 'description' => 'Delete customer'],
            ],
            'pop-settings' => [
                ['name' => 'pop-settings.view', 'description' => 'View POP settings'],
                ['name' => 'pop-settings.edit', 'description' => 'Edit POP settings'],
            ],
            'payment-gateways' => [
                ['name' => 'payment-gateways.view', 'description' => 'View payment gateways'],
                ['name' => 'payment-gateways.create', 'description' => 'Create payment gateway'],
                ['name' => 'payment-gateways.edit', 'description' => 'Edit payment gateway'],
                ['name' => 'payment-gateways.delete', 'description' => 'Delete payment gateway'],
            ],
            'notification-settings' => [
                ['name' => 'notification-settings.view', 'description' => 'View notification settings'],
                ['name' => 'notification-settings.edit', 'description' => 'Edit notification settings'],
            ],
            'message-templates' => [
                ['name' => 'message-templates.view', 'description' => 'View message templates'],
                ['name' => 'message-templates.edit', 'description' => 'Edit message templates'],
                ['name' => 'message-templates.reset', 'description' => 'Reset message templates'],
                ['name' => 'message-templates.preview', 'description' => 'Preview message templates'],
                ['name' => 'message-templates.send-test', 'description' => 'Send test messages'],
            ],
            'odcs' => [
                ['name' => 'odcs.view', 'description' => 'View ODC list'],
                ['name' => 'odcs.create', 'description' => 'Create new ODC'],
                ['name' => 'odcs.edit', 'description' => 'Edit ODC'],
                ['name' => 'odcs.delete', 'description' => 'Delete ODC'],
            ],
            'odps' => [
                ['name' => 'odps.view', 'description' => 'View ODP list'],
                ['name' => 'odps.create', 'description' => 'Create new ODP'],
                ['name' => 'odps.edit', 'description' => 'Edit ODP'],
                ['name' => 'odps.delete', 'description' => 'Delete ODP'],
            ],
            'olts' => [
                ['name' => 'olts.view', 'description' => 'View OLT list'],
                ['name' => 'olts.create', 'description' => 'Create new OLT'],
                ['name' => 'olts.edit', 'description' => 'Edit OLT'],
                ['name' => 'olts.delete', 'description' => 'Delete OLT'],
                ['name' => 'olts.sync', 'description' => 'Sync OLT data'],
            ],
            'onus' => [
                ['name' => 'onus.view', 'description' => 'View ONU list'],
                ['name' => 'onus.create', 'description' => 'Create new ONU'],
                ['name' => 'onus.edit', 'description' => 'Edit ONU'],
                ['name' => 'onus.delete', 'description' => 'Delete ONU'],
                ['name' => 'onus.register', 'description' => 'Register ONU on OLT'],
                ['name' => 'onus.unregister', 'description' => 'Unregister ONU from OLT'],
                ['name' => 'onus.reboot', 'description' => 'Reboot ONU'],
            ],
            'network-map' => [
                ['name' => 'network-map.view', 'description' => 'View network map'],
            ],
        ];
    }

    /**
     * Check if route should be skipped
     */
    protected function shouldSkipRoute(string $name): bool
    {
        $skipPrefixes = [
            'ignition.',
            'sanctum.',
            'livewire.',
            'debugbar.',
            'telescope.',
            'horizon.',
        ];

        $skipExact = [
            'login',
            'logout',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'verification.',
            'home',
        ];

        foreach ($skipPrefixes as $prefix) {
            if (Str::startsWith($name, $prefix)) {
                return true;
            }
        }

        foreach ($skipExact as $exact) {
            if ($name === $exact || Str::startsWith($name, $exact)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert route name to permission name
     */
    protected function routeNameToPermission(string $routeName): ?string
    {
        // Convert admin.users.index -> users.view
        // Convert admin.users.create -> users.create
        // etc.
        
        $parts = explode('.', $routeName);
        
        // Remove 'admin' prefix if exists
        if ($parts[0] === 'admin' && count($parts) > 1) {
            array_shift($parts);
        }

        if (count($parts) < 2) {
            return null;
        }

        $module = $parts[0];
        $action = end($parts);

        // Map Laravel resource actions to permission actions
        $actionMap = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete',
        ];

        $permAction = $actionMap[$action] ?? $action;

        return $module . '.' . $permAction;
    }

    /**
     * Get permission group from route name
     */
    protected function getPermissionGroup(string $routeName): string
    {
        $parts = explode('.', $routeName);
        
        if ($parts[0] === 'admin' && count($parts) > 1) {
            return $parts[1];
        }

        return $parts[0];
    }
}
