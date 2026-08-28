<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\NotificationSetting;
use App\Models\PopSetting;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BillingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:reminder 
                            {--pop= : Send only for specific POP ID}
                            {--days-before=3 : Days before due date to send reminder}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminder notifications to customers with pending invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting payment reminder process...');
        
        $popId = $this->option('pop');
        $daysBefore = (int) $this->option('days-before');
        $dryRun = $this->option('dry-run');
        
        // Calculate target date
        $targetDate = now()->addDays($daysBefore)->toDateString();
        
        $this->info("Looking for invoices due on or before: {$targetDate}");
        
        // Get pending invoices approaching due date
        $invoices = CustomerInvoice::with(['customer', 'pop'])
            ->where('status', 'pending')
            ->whereDate('due_date', '<=', $targetDate)
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->get();
        
        $this->info("Found {$invoices->count()} invoices to remind");
        
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            
            if (!$customer) {
                $this->warn("Skipped {$invoice->invoice_number}: Customer not found");
                continue;
            }
            
            // Check if reminder is enabled for this POP
            $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();
            if ($popSetting && !($popSetting->reminder_enabled ?? true)) {
                $skipped++;
                continue;
            }
            
            // Check NotificationSetting reminder_days_before
            $notifSetting = NotificationSetting::where('user_id', $invoice->pop_id)->first();
            $reminderDays = $notifSetting?->reminder_days_before ?? [2, 1];
            $daysUntilDue = (int) now()->diffInDays($invoice->due_date, false);
            
            // Only send if today matches one of the reminder_days_before OR if overdue
            $shouldSend = $daysUntilDue < 0 || in_array(abs($daysUntilDue), $reminderDays);
            
            if (!$shouldSend) {
                $skipped++;
                continue;
            }
            
            $statusText = $daysUntilDue < 0 ? 'OVERDUE' : ($daysUntilDue == 0 ? 'TODAY' : "H-{$daysUntilDue}");
            
            if ($dryRun) {
                $this->line("  - {$customer->name} ({$invoice->invoice_number}): Rp " . 
                           number_format($invoice->remaining_amount) . " - Due: {$statusText}");
                $sent++;
                continue;
            }
            
            try {
                $this->sendReminder($invoice, $customer, $daysUntilDue);
                
                $this->line("  - Sent reminder to {$customer->name} ({$statusText})");
                $sent++;
                
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for {$invoice->invoice_number}: " . $e->getMessage());
                $this->error("  - Failed for {$customer->name}: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Would send {$sent} reminders (skipped {$skipped}).");
        } else {
            $this->info("Reminder process completed!");
            $this->info("Sent: {$sent} | Skipped: {$skipped} | Failed: {$failed}");
        }
        
        // Update overdue invoices
        $this->updateOverdueInvoices($popId);
        
        return 0;
    }
    
    /**
     * Send reminder notification via NotificationService
     */
    protected function sendReminder(CustomerInvoice $invoice, Customer $customer, int $daysUntilDue): void
    {
        $invoiceData = [
            'invoice_number' => $invoice->invoice_number,
            'due_date' => $invoice->due_date->format('d F Y'),
            'amount' => 'Rp ' . number_format($invoice->remaining_amount, 0, ',', '.'),
            'total_amount' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
            'days_left' => abs($daysUntilDue),
            'days_overdue' => $daysUntilDue < 0 ? abs($daysUntilDue) : 0,
            'payment_url' => route('pelanggan.invoice', $invoice->id),
        ];
        
        if ($daysUntilDue < 0) {
            // Overdue notification
            app(NotificationService::class)->sendOverdue($customer, $invoiceData);
        } else {
            // Reminder notification
            app(NotificationService::class)->sendInvoiceReminder($customer, $invoiceData);
        }
    }
    
    /**
     * Update invoices that are overdue
     */
    protected function updateOverdueInvoices(?string $popId): void
    {
        $updated = CustomerInvoice::where('status', 'pending')
            ->whereDate('due_date', '<', now())
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->update(['status' => 'overdue']);
        
        if ($updated > 0) {
            $this->info("Marked {$updated} invoices as overdue");
        }
    }
}
