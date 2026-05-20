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

        // Find suspended customers with NO remaining pending/overdue invoices
        $customers = Customer::with(['router', 'package'])
            ->where('status', 'suspended')
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->whereDoesntHave('invoices', fn($q) => $q->whereIn('status', ['pending', 'overdue']))
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
