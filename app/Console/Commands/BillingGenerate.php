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
    protected $description = 'Generate invoice awal bulan untuk siklus billing masing-masing pelanggan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation...');
        
        $popId = $this->option('pop');
        $dryRun = $this->option('dry-run');
        $requestedBillingDay = $this->option('billing-day') ? max(1, min(28, (int) $this->option('billing-day'))) : null;
        $this->info('Date: ' . now()->format('Y-m-d'));
        
        // Get POPs to process
        $pops = User::role('admin-pop')
            ->when($popId, fn($q) => $q->where('id', $popId))
            ->get();
        
        $totalGenerated = 0;
        $totalSkipped = 0;
        
        foreach ($pops as $pop) {
            $this->info("Processing POP: {$pop->name}");
            
            $popSetting = PopSetting::where('user_id', $pop->id)->first();
            $requestedDueDate = $requestedBillingDay
                ? Carbon::create(now()->year, now()->month, $requestedBillingDay)->startOfDay()
                : null;

            if ($requestedDueDate?->lt(now()->startOfDay())) {
                $requestedDueDate->addMonth();
            }
            $currentMonth = now()->startOfMonth();
            
            // Generate the whole current month's billing cycle in one run.
            // A customer's period starts on its billing day, not necessarily
            // on calendar day 1. --billing-day remains available for manual
            // targeted generation/backfill.
            $customers = Customer::where('pop_id', $pop->id)
                ->whereIn('status', ['active', 'suspended'])
                ->whereNotNull('package_id')
                ->when($requestedBillingDay, function ($query) use ($requestedBillingDay) {
                    $query->where(function ($q) use ($requestedBillingDay) {
                        $q->where('billing_day', $requestedBillingDay);
                        if ($requestedBillingDay === 1) {
                            $q->orWhereNull('billing_day');
                        }
                    });
                })
                ->with(['package', 'invoices:id,customer_id,period_start,period_end'])
                ->get();

            $pendingInvoices = [];
            foreach ($customers as $customer) {
                $billingDay = min(28, max(1, (int) ($customer->billing_day ?: 1)));
                $periodStart = $requestedDueDate
                    ? $requestedDueDate->copy()
                    : $currentMonth->copy()->setDay($billingDay);
                $periodEnd = $periodStart->copy()->addMonth()->subDay();
                $alreadyExists = $customer->invoices->contains(fn ($invoice) =>
                    $invoice->period_start?->toDateString() === $periodStart->toDateString()
                    && $invoice->period_end?->toDateString() === $periodEnd->toDateString()
                );
                if ($alreadyExists) {
                    continue;
                }
                $pendingInvoices[] = compact('customer', 'billingDay', 'periodStart', 'periodEnd');
            }

            $description = $requestedDueDate
                ? "due {$requestedDueDate->format('Y-m-d')} (billing_day={$requestedBillingDay})"
                : 'periode bulan berjalan';
            $this->info("POP {$pop->name}: {$description}; invoice awal bulan; found " . count($pendingInvoices) . ' customers');

            if ($dryRun) {
                foreach ($pendingInvoices as $item) {
                    $customer = $item['customer'];
                    $this->line("  - {$customer->name} ({$customer->customer_id}): {$customer->package?->name} [billing_day={$item['billingDay']}]");
                }
                $totalSkipped += count($pendingInvoices);
                continue;
            }
            
            DB::beginTransaction();
            
            try {
                foreach ($pendingInvoices as $item) {
                    $customer = $item['customer'];
                    $periodStart = $item['periodStart'];
                    $periodEnd = $item['periodEnd'];
                    $dueDate = $periodStart->copy();
                    
                    $subtotal = $customer->package->price;
                    $taxAmount = 0;
                    
                    if ($popSetting?->ppn_enabled) {
                        $taxAmount = $subtotal * ($popSetting->ppn_percentage / 100);
                    }
                    
                    $totalAmount = $subtotal + $taxAmount;
                    $invoice = CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $pop->id,
                        'invoice_number' => CustomerInvoice::generateInvoiceNumber($pop->id),
                        'invoice_date' => now(),
                        'due_date' => $dueDate->copy(),
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
