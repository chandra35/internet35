<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerUnsuspendService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BillingAutoUnsuspend extends Command
{
    protected $signature = 'billing:auto-unsuspend
                            {--pop= : Process only for specific POP ID}
                            {--dry-run : Preview without unsuspending}';

    protected $description = 'Automatically unsuspend/buka-isolir customers who have cleared all overdue invoices';

    protected CustomerUnsuspendService $unsuspendService;

    public function __construct(CustomerUnsuspendService $unsuspendService)
    {
        parent::__construct();
        $this->unsuspendService = $unsuspendService;
    }

    public function handle()
    {
        $this->info('Starting auto-unsuspend process...');

        $popId  = $this->option('pop');
        $dryRun = $this->option('dry-run');

        // Find suspended customers with no overdue or past-due unpaid invoices.
        $today = now()->startOfDay()->toDateString();
        $customers = Customer::with(['router', 'package'])
            ->where('status', 'suspended')
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->whereDoesntHave('invoices', function ($q) use ($today) {
                $q->where('status', 'overdue')
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereIn('status', ['pending', 'partial'])
                          ->whereDate('due_date', '<=', $today);
                  });
            })
            ->get();

        $this->info("Found {$customers->count()} suspended customers eligible for unsuspend");

        $unsuspended = 0;
        $failed      = 0;

        foreach ($customers as $customer) {
            if ($dryRun) {
                $this->line("  [DRY] Would unsuspend: {$customer->name} ({$customer->customer_id})");
                $unsuspended++;
                continue;
            }

            try {
                $mikrotikResult = $this->unsuspendService->unsuspend($customer);

                if ($mikrotikResult === 'unsuspended' || (!$customer->router && $customer->fresh()->status === 'active')) {
                    $this->line("  ✓ Unsuspended {$customer->name} ({$customer->customer_id}) [mikrotik: {$mikrotikResult}]");
                    $unsuspended++;

                    Log::info("Auto-unsuspend: unsuspended {$customer->customer_id} ({$customer->name}) [mikrotik: {$mikrotikResult}]");

                    // Send activation notification
                    try {
                        app(NotificationService::class)->sendActivated($customer, [
                            'activate_date' => now()->format('d F Y H:i'),
                            'reason'        => 'Tagihan telah dilunasi',
                        ]);
                    } catch (\Exception $notifErr) {
                        Log::warning("Auto-unsuspend: failed notification for {$customer->customer_id}: " . $notifErr->getMessage());
                    }
                } else {
                    $this->error("  ✗ Unsuspend skipped for {$customer->name}: MikroTik returned [{$mikrotikResult}]");
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Auto-unsuspend failed for {$customer->customer_id}: " . $e->getMessage());
                $this->error("  ✗ Failed for {$customer->name}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Would unsuspend {$unsuspended} customers.");
        } else {
            $this->info("Auto-unsuspend completed! Unsuspended: {$unsuspended} | Failed: {$failed}");
        }

        return 0;
    }
}
