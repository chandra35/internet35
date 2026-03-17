<?php

namespace App\Services;

use App\Models\CustomerInvoice;
use App\Models\PopSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Generate PDF for an invoice and return the PDF instance.
     */
    public function generate(CustomerInvoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['customer.package', 'customer.village', 'customer.district', 'customer.city', 'customer.province', 'payments']);

        $customer = $invoice->customer;
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();

        $data = [
            'invoice' => $invoice,
            'customer' => $customer,
            'popSetting' => $popSetting,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Generate and store the PDF, update invoice record.
     */
    public function generateAndStore(CustomerInvoice $invoice): string
    {
        $pdf = $this->generate($invoice);

        $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $invoice->update([
            'pdf_path' => $filename,
            'printed_at' => now(),
            'print_count' => ($invoice->print_count ?? 0) + 1,
        ]);

        return $filename;
    }

    /**
     * Stream PDF directly (for download/preview).
     */
    public function stream(CustomerInvoice $invoice)
    {
        $pdf = $this->generate($invoice);
        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Download PDF.
     */
    public function download(CustomerInvoice $invoice)
    {
        $pdf = $this->generate($invoice);
        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
