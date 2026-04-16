<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionScannerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Sync permissions from scanner
        $scanner = new PermissionScannerService();
        $scanner->syncToDatabase();

        // Create roles
        $superadmin = Role::firstOrCreate(
            ['name' => 'superadmin', 'guard_name' => 'web'],
            ['description' => 'Super Administrator dengan akses penuh ke semua fitur']
        );

        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['description' => 'Administrator yang mengelola sistem']
        );

        $adminPop = Role::firstOrCreate(
            ['name' => 'admin-pop', 'guard_name' => 'web'],
            ['description' => 'Admin POP yang mengelola area tertentu']
        );

        $teknisi = Role::firstOrCreate(
            ['name' => 'teknisi', 'guard_name' => 'web'],
            ['description' => 'Teknisi lapangan yang menangani instalasi dan perbaikan']
        );

        $support = Role::firstOrCreate(
            ['name' => 'support', 'guard_name' => 'web'],
            ['description' => 'Tim support yang menangani keluhan pelanggan']
        );

        $client = Role::firstOrCreate(
            ['name' => 'client', 'guard_name' => 'web'],
            ['description' => 'Pelanggan yang dapat melakukan pembayaran']
        );

        // Give all permissions to superadmin
        $allPermissions = Permission::all();
        $superadmin->syncPermissions($allPermissions);

        // Give admin permissions (operational access, not system-level)
        $adminPermissions = Permission::whereNotIn('name', [
            // Role & permission management — superadmin only
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'permissions.scan',
            // User management — superadmin only
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            // POP monitoring — superadmin only
            'pop-settings.monitoring',
            'pop-settings.copy',
            // Data maintenance — superadmin only
            'data-maintenance.view',
            'data-maintenance.clear-customers',
            'data-maintenance.clear-packages',
            'data-maintenance.clear-profiles',
            // Residents — superadmin only
            'residents.view',
            'residents.import',
            'residents.delete',
            'residents.grant-access',
        ])->get();
        $admin->syncPermissions($adminPermissions);

        // Give admin-pop limited permissions
        // admin-pop can only manage their own routers and see their own created users
        $adminPopPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'profile.view',
            'profile.edit',
            'activity-logs.view',
            'routers.view',
            'routers.create',
            'routers.edit',
            'routers.delete',
            'routers.manage',
            // Package permissions
            'packages.view',
            'packages.create',
            'packages.edit',
            'packages.delete',
            'packages.sync',
            // Customer permissions
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            // POP Settings permissions
            'pop-settings.view',
            'pop-settings.edit',
            'payment-gateways.view',
            'payment-gateways.create',
            'payment-gateways.edit',
            'payment-gateways.delete',
            'notification-settings.view',
            'notification-settings.edit',
            // Message Templates permissions
            'message-templates.view',
            'message-templates.edit',
            'message-templates.reset',
            'message-templates.preview',
            'message-templates.send-test',
            // Staff management permissions
            'staff.view',
            'staff.create',
            'staff.edit',
            'staff.delete',
            // ODC/ODP/Network Map permissions
            'odcs.view',
            'odcs.create',
            'odcs.edit',
            'odcs.delete',
            'odps.view',
            'odps.create',
            'odps.edit',
            'odps.delete',
            'network-map.view',
            // OLT/ONU permissions
            'olts.view',
            'olts.create',
            'olts.edit',
            'olts.delete',
            'olts.sync',
            'onus.view',
            'onus.create',
            'onus.edit',
            'onus.delete',
            'onus.register',
            'onus.unregister',
            'onus.reboot',
            // Invoice permissions
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.generate',
            'invoices.mark-paid',
            'invoices.cancel',
            'invoices.print',
            'invoices.send-reminder',
            // Scheduler permissions
            'scheduler.view',
            'scheduler.create',
            'scheduler.edit',
            'scheduler.delete',
            'scheduler.toggle',
            'scheduler.run',
            'scheduler.logs',
        ])->get();
        $adminPop->syncPermissions($adminPopPermissions);

        // Give teknisi permissions
        $teknisiPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'profile.view',
            'profile.edit',
            'customers.view',
            'routers.view',
            // OLT/ONU view permissions for teknisi
            'olts.view',
            'onus.view',
            'onus.reboot',
            'odcs.view',
            'odps.view',
            'network-map.view',
        ])->get();
        $teknisi->syncPermissions($teknisiPermissions);

        // Give support permissions
        $supportPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'profile.view',
            'profile.edit',
            'customers.view',
            'customers.edit',
        ])->get();
        $support->syncPermissions($supportPermissions);

        // Give client permissions
        $clientPermissions = Permission::whereIn('name', [
            'profile.view',
            'profile.edit',
        ])->get();
        $client->syncPermissions($clientPermissions);

        // Create default superadmin user
        $user = User::firstOrCreate(
            ['email' => 'superadmin@internet35.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $user->assignRole('superadmin');

        // Create demo admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@internet35.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
