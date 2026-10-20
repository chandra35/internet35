<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CustomerInvoice;

class BillingAndInvoiceLogicTest extends TestCase
{
    /**
     * Test invoice remaining amount calculation.
     */
    public function test_invoice_remaining_amount_calculation(): void
    {
        $invoice = new CustomerInvoice([
            'total_amount' => 150000.00,
            'paid_amount' => 50000.00,
        ]);

        $this->assertEquals(100000.00, $invoice->remaining_amount);
    }

    /**
     * Test full payment results in zero remaining amount.
     */
    public function test_full_payment_leaves_zero_remaining(): void
    {
        $invoice = new CustomerInvoice([
            'total_amount' => 220000.00,
            'paid_amount' => 220000.00,
        ]);

        $this->assertEquals(0, $invoice->remaining_amount);
    }

    /**
     * Test status labels and color codes mapping.
     */
    public function test_status_labels_and_colors(): void
    {
        $pendingInvoice = new CustomerInvoice(['status' => 'pending']);
        $this->assertEquals('Belum Dibayar', $pendingInvoice->status_label);
        $this->assertEquals('warning', $pendingInvoice->status_color);

        $paidInvoice = new CustomerInvoice(['status' => 'paid']);
        $this->assertEquals('Lunas', $paidInvoice->status_label);
        $this->assertEquals('success', $paidInvoice->status_color);

        $overdueInvoice = new CustomerInvoice(['status' => 'overdue']);
        $this->assertEquals('Jatuh Tempo', $overdueInvoice->status_label);
        $this->assertEquals('danger', $overdueInvoice->status_color);
    }
}
