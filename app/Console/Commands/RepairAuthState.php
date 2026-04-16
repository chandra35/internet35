<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PermissionScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class RepairAuthState extends Command
{
    protected $signature = 'app:repair-auth
                            {--user= : Inspect a user by email or UUID after the repair}
                            {--flush-sessions : Delete all active sessions after database restore}
                            {--reseed-rbac : Re-seed default roles and permissions}';

    protected $description = 'Refresh permission cache, sync missing permissions, and optionally clear sessions';

    public function handle(): int
    {
        $this->info('Refreshing permission cache...');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->callSilent('cache:clear');

        $syncResult = app(PermissionScannerService::class)->syncToDatabase();
        $createdCount = count($syncResult['created'] ?? []);
        $existingCount = count($syncResult['existing'] ?? []);

        if ($this->option('reseed-rbac')) {
            $this->warn('Re-seeding default roles and permissions...');
            $this->callSilent('db:seed', [
                '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                '--force' => true,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Permissions synced. Created: {$createdCount}, existing: {$existingCount}");

        if ($this->option('flush-sessions')) {
            $this->flushSessions();
        }

        $userIdentifier = trim((string) $this->option('user'));
        if ($userIdentifier !== '') {
            $this->inspectUser($userIdentifier);
        }

        $this->info('Auth repair complete.');

        return self::SUCCESS;
    }

    protected function flushSessions(): void
    {
        $sessionTable = config('session.table', 'sessions');

        if (!Schema::hasTable($sessionTable)) {
            $this->warn("Session table [{$sessionTable}] tidak ditemukan, skip flush session.");
            return;
        }

        $deleted = DB::table($sessionTable)->delete();
        $this->info("Deleted {$deleted} session rows.");
    }

    protected function inspectUser(string $identifier): void
    {
        $user = User::query()
            ->where('id', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            $this->warn("User [{$identifier}] tidak ditemukan.");
            return;
        }

        $user->load('roles');

        $roles = $user->roles->pluck('name')->implode(', ');
        $permissions = $user->getAllPermissions()->pluck('name')->sort()->values();

        $this->newLine();
        $this->info("User check: {$user->email} ({$user->id})");
        $this->line('Active: ' . ($user->is_active ? 'yes' : 'no'));
        $this->line('Roles: ' . ($roles !== '' ? $roles : '-'));
        $this->line('Has dashboard.view: ' . ($user->can('dashboard.view') ? 'yes' : 'no'));

        $preview = $permissions->take(15)->implode(', ');
        $this->line('Permissions preview: ' . ($preview !== '' ? $preview : '-'));

        if ($permissions->count() > 15) {
            $this->line('Permissions total: ' . $permissions->count());
        }
    }
}
