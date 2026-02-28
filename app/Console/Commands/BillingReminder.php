<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\PopSetting;
use App\Models\User;
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
        $failed = 0;
        
        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            
            if (!$customer) {
                $this->warn("Skipped {$invoice->invoice_number}: Customer not found");
                continue;
            }
            
            $daysUntilDue = now()->diffInDays($invoice->due_date, false);
            $status = $daysUntilDue < 0 ? 'OVERDUE' : ($daysUntilDue == 0 ? 'TODAY' : "{$daysUntilDue} days");
            
            if ($dryRun) {
                $this->line("  - {$customer->name} ({$invoice->invoice_number}): Rp " . 
                           number_format($invoice->remaining_amount) . " - Due: {$status}");
                continue;
            }
            
            try {
                // Send reminder notification
                // This would integrate with NotificationService
                $this->sendReminder($invoice, $customer);
                
                $this->line("  - Sent reminder to {$customer->name} ({$customer->phone})");
                $sent++;
                
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for {$invoice->invoice_number}: " . $e->getMessage());
                $this->error("  - Failed for {$customer->name}: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Would send {$invoices->count()} reminders.");
        } else {
            $this->info("Reminder process completed!");
            $this->info("Sent: {$sent} reminders");
            if ($failed > 0) {
                $this->warn("Failed: {$failed} reminders");
            }
        }
        
        // Update overdue invoices
        $this->updateOverdueInvoices($popId);
        
        return 0;
    }
    
    /**
     * Send reminder notification
     */
    protected function sendReminder(CustomerInvoice $invoice, Customer $customer): void
    {
        // Get POP settings for notification channels
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();
        
        // Build message
        $daysUntilDue = now()->diffInDays($invoice->due_date, false);
        
        if ($daysUntilDue < 0) {
            $message = "Tagihan Anda {$invoice->invoice_number} sudah melewati jatuh tempo. ";
            $message .= "Mohon segera lakukan pembayaran sebesar Rp " . number_format($invoice->remaining_amount, 0, ',', '.') . " ";
            $message .= "untuk menghindari pemutusan layanan.";
        } elseif ($daysUntilDue == 0) {
            $message = "Tagihan Anda {$invoice->invoice_number} jatuh tempo HARI INI. ";
            $message .= "Mohon segera lakukan pembayaran sebesar Rp " . number_format($invoice->remaining_amount, 0, ',', '.') . ".";
        } else {
            $message = "Pengingat: Tagihan Anda {$invoice->invoice_number} akan jatuh tempo dalam {$daysUntilDue} hari. ";
            $message .= "Total tagihan: Rp " . number_format($invoice->remaining_amount, 0, ',', '.') . ".";
        }
        
        // TODO: Integrate with actual notification service
        // For now, just log the message
        Log::info("Payment reminder for {$customer->name}: {$message}");
        
        // You would call your notification service here:
        // app(NotificationService::class)->sendPaymentReminder($customer, $invoice, $message);
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
