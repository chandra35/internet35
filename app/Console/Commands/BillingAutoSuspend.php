<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Services\CustomerUnsuspendService;
use App\Services\NotificationService;
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

    protected CustomerUnsuspendService $unsuspendService;

    public function __construct(CustomerUnsuspendService $unsuspendService)
    {
        parent::__construct();
        $this->unsuspendService = $unsuspendService;
    }

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
                // Isolir in Mikrotik: change PPP profile to 'isolir' + disconnect
                $mikrotikResult = $this->unsuspendService->isolir($customer);
                
                // Update customer status
                $customer->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                    'suspend_reason' => 'Auto-isolir: tagihan belum dibayar',
                    'mikrotik_status' => ($mikrotikResult === 'isolated') ? 'isolated' : $customer->mikrotik_status,
                ]);
                
                // Update invoices status to overdue
                CustomerInvoice::whereIn('id', $overdueInvoices->pluck('id'))
                    ->where('status', 'pending')
                    ->update(['status' => 'overdue']);
                
                $this->line("  ✓ Isolated {$customer->name} ({$customer->customer_id}) — Rp " . 
                           number_format($totalOverdue) . " overdue [mikrotik: {$mikrotikResult}]");
                $suspended++;
                
                Log::info("Auto-isolir: isolated {$customer->customer_id} ({$customer->name}), overdue: Rp " . number_format($totalOverdue));
                
                // Send notification to customer
                try {
                    app(NotificationService::class)->sendIsolated($customer, [
                        'isolate_reason' => 'Auto-isolir: tagihan Rp ' . number_format($totalOverdue, 0, ',', '.') . ' belum dibayar',
                        'isolate_date' => now()->format('d F Y H:i'),
                        'overdue_amount' => 'Rp ' . number_format($totalOverdue, 0, ',', '.'),
                    ]);
                } catch (\Exception $notifErr) {
                    Log::warning("Failed to send auto-isolir notification to {$customer->customer_id}: " . $notifErr->getMessage());
                }

            } catch (\Exception $e) {
                Log::error("Auto-isolir failed for {$customer->customer_id}: " . $e->getMessage());
                $this->error("  ✗ Failed for {$customer->name}: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Would isolir {$suspended} customers (skipped {$skipped}).");
        } else {
            $this->info("Auto-isolir process completed!");
            $this->info("Isolated: {$suspended} | Skipped (not overdue): {$skipped} | Failed: {$failed}");
        }
        
        return 0;
    }
}
