<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\PopSetting;
use App\Models\User;
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
                            {--period-start= : Period start date (Y-m-d)}
                            {--period-end= : Period end date (Y-m-d)}
                            {--dry-run : Preview without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for all active customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation...');
        
        $popId = $this->option('pop');
        $dryRun = $this->option('dry-run');
        
        // Determine period
        $periodStart = $this->option('period-start') 
            ? \Carbon\Carbon::parse($this->option('period-start'))
            : now()->startOfMonth();
        $periodEnd = $this->option('period-end')
            ? \Carbon\Carbon::parse($this->option('period-end'))
            : now()->endOfMonth();
        
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
            
            // Get active customers without invoice for this period
            $customers = Customer::where('pop_id', $pop->id)
                ->where('status', 'active')
                ->whereNotNull('package_id')
                ->with('package')
                ->whereDoesntHave('invoices', function($q) use ($periodStart, $periodEnd) {
                    $q->where('period_start', $periodStart)
                      ->where('period_end', $periodEnd);
                })
                ->get();
            
            $this->info("Found {$customers->count()} customers to process");
            
            if ($dryRun) {
                foreach ($customers as $customer) {
                    $this->line("  - {$customer->name} ({$customer->customer_id}): {$customer->package?->name}");
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
                    
                    CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $pop->id,
                        'invoice_number' => CustomerInvoice::generateInvoiceNumber($pop->id),
                        'invoice_date' => now(),
                        'due_date' => now()->addDays($dueDays),
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
                    
                    $this->line("  - Created invoice for {$customer->name}");
                    $totalGenerated++;
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
