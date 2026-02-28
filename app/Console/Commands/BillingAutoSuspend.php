<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Helpers\Mikrotik\MikrotikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BillingAutoSuspend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:auto-suspend 
                            {--pop= : Process only for specific POP ID}
                            {--grace-days=0 : Override grace period days (0 = use per-customer setting)}
                            {--dry-run : Preview without suspending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically suspend/isolate customers with overdue invoices (only those with auto_isolir enabled)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-isolir/suspend process...');
        
        $popId = $this->option('pop');
        $globalGraceDays = $this->option('grace-days');
        $dryRun = $this->option('dry-run');
        
        // Get customers with auto_isolir enabled, active status, and overdue invoices
        $customers = Customer::with(['router', 'package', 'invoices'])
            ->where('status', 'active')
            ->where('auto_isolir', true)
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->whereHas('invoices', function($q) {
                $q->whereIn('status', ['pending', 'overdue']);
            })
            ->get();
        
        $this->info("Found {$customers->count()} customers with auto-isolir enabled to check");
        
        $suspended = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($customers as $customer) {
            // Calculate grace period: use command option if > 0, otherwise per-customer setting
            $graceDays = $globalGraceDays > 0 ? (int) $globalGraceDays : ($customer->grace_period_days ?? 3);
            $cutoffDate = now()->subDays($graceDays)->toDateString();
            
            // Get overdue invoices past grace period
            $overdueInvoices = $customer->invoices
                ->whereIn('status', ['pending', 'overdue'])
                ->filter(fn($inv) => $inv->due_date && $inv->due_date->toDateString() < $cutoffDate);
            
            if ($overdueInvoices->isEmpty()) {
                $skipped++;
                continue;
            }
            
            $totalOverdue = $overdueInvoices->sum('remaining_amount');
            
            if ($dryRun) {
                $this->line("  [DRY] {$customer->name} ({$customer->customer_id}): Rp " . 
                           number_format($totalOverdue) . " overdue, grace: {$graceDays}d");
                $suspended++;
                continue;
            }
            
            try {
                // Suspend in Mikrotik if router is configured
                $mikrotikResult = 'no_router';
                if ($customer->router && $customer->pppoe_username) {
                    $mikrotikResult = $this->suspendInMikrotik($customer);
                }
                
                // Update customer status
                $customer->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                    'suspend_reason' => 'Auto-isolir: tagihan belum dibayar',
                    'mikrotik_status' => ($mikrotikResult === 'disabled') ? 'disabled' : $customer->mikrotik_status,
                ]);
                
                // Update invoices status to overdue
                CustomerInvoice::whereIn('id', $overdueInvoices->pluck('id'))
                    ->where('status', 'pending')
                    ->update(['status' => 'overdue']);
                
                $this->line("  ✓ Suspended {$customer->name} ({$customer->customer_id}) — Rp " . 
                           number_format($totalOverdue) . " overdue [mikrotik: {$mikrotikResult}]");
                $suspended++;
                
                Log::info("Auto-isolir: suspended {$customer->customer_id} ({$customer->name}), overdue: Rp " . number_format($totalOverdue));
                
            } catch (\Exception $e) {
                Log::error("Auto-isolir failed for {$customer->customer_id}: " . $e->getMessage());
                $this->error("  ✗ Failed for {$customer->name}: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Would suspend {$suspended} customers (skipped {$skipped}).");
        } else {
            $this->info("Auto-isolir process completed!");
            $this->info("Suspended: {$suspended} | Skipped (not overdue): {$skipped} | Failed: {$failed}");
        }
        
        return 0;
    }
    
    /**
     * Suspend customer in Mikrotik by disabling PPP Secret
     * 
     * @return string Result status: 'disabled', 'not_found', 'not_connected', 'error'
     */
    protected function suspendInMikrotik(Customer $customer): string
    {
        try {
            $router = $customer->router;
            
            if (!$router || !$router->is_active) {
                return 'no_router';
            }
            
            $mikrotik = new MikrotikService();
            
            if (!$mikrotik->connectRouter($router)) {
                Log::warning("Auto-isolir: Cannot connect to router {$router->name} for {$customer->customer_id}");
                return 'not_connected';
            }
            
            // Lookup PPP secret by username to get its .id
            $secret = $mikrotik->getPppSecretByName($customer->pppoe_username);
            
            if (!$secret) {
                Log::warning("Auto-isolir: PPP secret not found for {$customer->pppoe_username} on {$router->name}");
                return 'not_found';
            }
            
            $secretId = $secret['.id'] ?? null;
            if (!$secretId) {
                return 'not_found';
            }
            
            // Disable the PPP secret
            $mikrotik->disablePppSecret($secretId);
            
            // Also disconnect active session if any
            $activeConnections = $mikrotik->getPppActive();
            foreach ($activeConnections as $conn) {
                if (($conn['name'] ?? '') === $customer->pppoe_username && isset($conn['.id'])) {
                    $mikrotik->disconnectPppUser($conn['.id']);
                    break;
                }
            }
            
            return 'disabled';
            
        } catch (\Exception $e) {
            Log::warning("Mikrotik isolir failed for {$customer->customer_id}: " . $e->getMessage());
            return 'error';
        }
    }
}
