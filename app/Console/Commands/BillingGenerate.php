<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\PopSetting;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate 
                            {--pop= : Generate only for specific POP ID}
                            {--billing-day= : Override billing day (1-28), default: today}
                            {--dry-run : Preview without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for customers whose billing_day matches today (or specified day)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation...');
        
        $popId = $this->option('pop');
        $dryRun = $this->option('dry-run');
        $billingDay = $this->option('billing-day') ? (int) $this->option('billing-day') : (int) now()->day;
        
        // Clamp billing day to 1-28
        $billingDay = max(1, min(28, $billingDay));
        
        $this->info("Billing day: {$billingDay} | Date: " . now()->format('Y-m-d'));
        
        // Calculate period for this billing_day
        // Period: from billing_day this month to (billing_day - 1) next month
        $periodStart = Carbon::create(now()->year, now()->month, $billingDay);
        // If billing_day is after today, it means the period started last month
        if ($billingDay > now()->day) {
            $periodStart->subMonth();
        }
        $periodEnd = $periodStart->copy()->addMonth()->subDay();
        
        $this->info("Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}");
        
        // Get POPs to process
        $pops = User::role('admin-pop')
            ->when($popId, fn($q) => $q->where('id', $popId))
            ->get();
        
        $totalGenerated = 0;
        $totalSkipped = 0;
        
        foreach ($pops as $pop) {
            $this->info("Processing POP: {$pop->name}");
            
            $popSetting = PopSetting::where('user_id', $pop->id)->first();
            $dueDays = $popSetting?->invoice_due_days ?? 7;
            
            // Keep each monthly period running for active and suspended
            // customers, so arrears are represented invoice-by-invoice per
            // month instead of stopping at the first suspension.
            // Also include customers with billing_day=NULL when processing day 1 (treat NULL as 1)
            $customers = Customer::where('pop_id', $pop->id)
                ->whereIn('status', ['active', 'suspended'])
                ->whereNotNull('package_id')
                ->where(function ($q) use ($billingDay) {
                    $q->where('billing_day', $billingDay);
                    if ($billingDay === 1) {
                        $q->orWhereNull('billing_day');
                    }
                })
                ->with('package')
                ->whereDoesntHave('invoices', function($q) use ($periodStart, $periodEnd) {
                    $q->where('period_start', $periodStart->toDateString())
                      ->where('period_end', $periodEnd->toDateString());
                })
                ->get();
            
            $this->info("Found {$customers->count()} customers (billing_day={$billingDay}) to process");
            
            if ($dryRun) {
                foreach ($customers as $customer) {
                    $this->line("  - {$customer->name} ({$customer->customer_id}): {$customer->package?->name} [billing_day={$customer->billing_day}]");
                }
                $totalSkipped += $customers->count();
                continue;
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($customers as $customer) {
                    if (!$customer->package) {
                        $this->warn("  - Skipped {$customer->name}: No package assigned");
                        $totalSkipped++;
                        continue;
                    }
                    
                    $subtotal = $customer->package->price;
                    $taxAmount = 0;
                    
                    if ($popSetting?->ppn_enabled) {
                        $taxAmount = $subtotal * ($popSetting->ppn_percentage / 100);
                    }
                    
                    $totalAmount = $subtotal + $taxAmount;
                    $dueDate = $periodStart->copy()->addDays($dueDays);
                    
                    $invoice = CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $pop->id,
                        'invoice_number' => CustomerInvoice::generateInvoiceNumber($pop->id),
                        'invoice_date' => now(),
                        'due_date' => $dueDate,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'items' => [
                            [
                                'description' => 'Layanan Internet ' . $customer->package->name,
                                'amount' => $subtotal,
                            ]
                        ],
                        'subtotal' => $subtotal,
                        'discount_amount' => 0,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'paid_amount' => 0,
                        'status' => 'pending',
                        'notes' => $popSetting?->invoice_notes,
                    ]);
                    
                    $this->line("  - Created invoice for {$customer->name} (due: {$dueDate->format('d M Y')})");
                    $totalGenerated++;

                    // Send invoice notification
                    try {
                        app(NotificationService::class)->sendInvoiceCreated($customer, [
                            'invoice_number' => $invoice->invoice_number,
                            'invoice_date' => now()->format('d F Y'),
                            'due_date' => $dueDate->format('d F Y'),
                            'total_amount' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                            'period' => $periodStart->format('d M Y') . ' - ' . $periodEnd->format('d M Y'),
                        ]);
                    } catch (\Exception $notifErr) {
                        Log::warning("Failed to send invoice notification to {$customer->customer_id}: " . $notifErr->getMessage());
                    }
                }
                
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Invoice generation failed for POP {$pop->name}: " . $e->getMessage());
                $this->error("Failed: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Would generate {$totalSkipped} invoices.");
        } else {
            $this->info("Generation completed!");
            $this->info("Generated: {$totalGenerated} invoices");
            $this->info("Skipped: {$totalSkipped} customers");
        }
        
        return 0;
    }
}
