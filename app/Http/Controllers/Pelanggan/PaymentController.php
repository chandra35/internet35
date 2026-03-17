<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Models\PaymentGateway;
use App\Services\PaymentGatewayService;
use App\Services\CustomerUnsuspendService;
use App\Services\InvoicePdfService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentGatewayService $paymentService;
    protected CustomerUnsuspendService $unsuspendService;

    public function __construct(PaymentGatewayService $paymentService, CustomerUnsuspendService $unsuspendService)
    {
        $this->paymentService = $paymentService;
        $this->unsuspendService = $unsuspendService;
    }

    /**
     * Show invoices list
     */
    public function invoices()
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return redirect()->route('pelanggan.dashboard');
        }
        
        $invoices = CustomerInvoice::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        
        return view('pelanggan.invoices', compact('customer', 'invoices'));
    }

    /**
     * Show single invoice
     */
    public function showInvoice(CustomerInvoice $invoice)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        // Ensure invoice belongs to this customer
        if (!$customer || $invoice->customer_id !== $customer->id) {
            abort(403);
        }
        
        $invoice->load(['customer', 'payments']);
        
        // Get available payment gateways for this POP
        $gateways = PaymentGateway::where('is_active', true)
            ->where('user_id', $customer->pop_id)
            ->get();

        $popSetting = \App\Models\PopSetting::where('user_id', $customer->pop_id)->first();

        return view('pelanggan.invoice-detail', compact('customer', 'invoice', 'gateways', 'popSetting'));
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(CustomerInvoice $invoice)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;

        if (!$customer || $invoice->customer_id !== $customer->id) {
            abort(403);
        }

        return app(InvoicePdfService::class)->download($invoice);
    }

    /**
     * Show payment history
     */
    public function history()
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return redirect()->route('pelanggan.dashboard');
        }
        
        $payments = CustomerPayment::where('customer_id', $customer->id)
            ->with('invoice')
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        
        return view('pelanggan.payment-history', compact('customer', 'payments'));
    }

    /**
     * Start payment process
     */
    public function pay(Request $request, CustomerInvoice $invoice)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        // Validate
        if (!$customer || $invoice->customer_id !== $customer->id) {
            return response()->json(['error' => 'Tidak ditemukan'], 404);
        }
        
        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Invoice sudah lunas'], 422);
        }
        
        $request->validate([
            'gateway_id' => 'required|uuid|exists:payment_gateways,id',
        ]);
        
        $gateway = PaymentGateway::findOrFail($request->gateway_id);
        
        DB::beginTransaction();
        try {
            // Create payment record
            $payment = CustomerPayment::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'pop_id' => $customer->pop_id,
                'payment_gateway_id' => $gateway->id,
                'payment_number' => CustomerPayment::generatePaymentNumber($customer->pop_id),
                'payment_method' => $gateway->gateway_type,
                'amount' => $invoice->remaining_amount ?? $invoice->total_amount,
                'status' => 'pending',
                'external_id' => 'PAY-' . strtoupper(uniqid()),
                'expired_at' => now()->addHours(24),
            ]);
            
            // Use PaymentGatewayService to create transaction
            $result = $this->paymentService->createTransaction($gateway, $payment, $customer);
            
            if (!$result['success']) {
                DB::rollBack();
                return response()->json(['error' => $result['message']], 500);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'payment_url' => $result['payment_url'],
                'message' => $result['message'],
                'is_demo' => $result['is_demo'] ?? false,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat memproses pembayaran'], 500);
        }
    }

    /**
     * Show payment confirmation page
     */
    public function confirm(CustomerPayment $payment)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer || $payment->customer_id !== $customer->id) {
            abort(403);
        }
        
        $payment->load(['invoice', 'paymentGateway']);
        
        return view('pelanggan.payment-confirm', compact('customer', 'payment'));
    }

    /**
     * Demo payment process page - simulates payment gateway
     */
    public function demoProcess(CustomerPayment $payment)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer || $payment->customer_id !== $customer->id) {
            abort(403);
        }
        
        $payment->load(['invoice', 'paymentGateway']);
        
        // Only allow demo processing for demo mode gateways
        if (!$payment->paymentGateway || $payment->paymentGateway->mode !== 'demo') {
            return redirect()->route('pelanggan.payment.confirm', $payment)
                ->with('error', 'Halaman ini hanya tersedia untuk mode demo');
        }
        
        if ($payment->status === 'success') {
            return redirect()->route('pelanggan.payment.confirm', $payment)
                ->with('success', 'Pembayaran sudah berhasil');
        }
        
        return view('pelanggan.payment-demo', compact('customer', 'payment'));
    }

    /**
     * Execute demo payment - simulate payment success
     */
    public function demoExecute(CustomerPayment $payment)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer || $payment->customer_id !== $customer->id) {
            return response()->json(['error' => 'Tidak ditemukan'], 404);
        }
        
        if (!$payment->paymentGateway || $payment->paymentGateway->mode !== 'demo') {
            return response()->json(['error' => 'Hanya tersedia untuk mode demo'], 422);
        }
        
        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Pembayaran tidak dalam status pending'], 422);
        }
        
        try {
            // Generate demo callback response
            $callbackData = $this->paymentService->processDemoCallback(
                $payment->paymentGateway,
                $payment
            );
            
            // Store callback data
            $payment->update([
                'gateway_response' => $callbackData,
            ]);
            
            // Mark as success
            $payment->markAsSuccess($callbackData['reference'] ?? $callbackData['transaction_id'] ?? 'DEMO-' . time());
            
            // Auto unsuspend if customer is suspended
            $unsuspendResult = null;
            if ($customer->status === 'suspended') {
                // Check if all overdue invoices are now paid
                $hasOverdueInvoices = $customer->invoices()
                    ->whereIn('status', ['pending', 'overdue'])
                    ->where('due_date', '<', now())
                    ->exists();
                
                if (!$hasOverdueInvoices) {
                    $unsuspendResult = $this->unsuspendService->unsuspend($customer);
                }
            }
            
            // Update gateway stats
            if ($payment->payment_gateway_id) {
                $gateway = $payment->paymentGateway;
                if ($gateway) {
                    $gateway->increment('total_transactions');
                    $gateway->increment('total_amount', $payment->amount);
                    $gateway->update(['last_transaction_at' => now()]);
                }
            }
            
            Log::info("[DEMO] Payment {$payment->payment_number} marked as success", [
                'customer' => $customer->customer_id,
                'amount' => $payment->amount,
                'unsuspend' => $unsuspendResult,
            ]);

            // Send payment success notification
            try {
                app(NotificationService::class)->sendPaymentSuccess($customer, [
                    'invoice_number' => $payment->invoice?->invoice_number ?? '-',
                    'payment_number' => $payment->payment_number,
                    'amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                    'payment_date' => now()->format('d F Y H:i'),
                    'payment_method' => $payment->payment_method ?? 'Demo',
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send payment success notification: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => '[DEMO] Pembayaran berhasil! ' . 
                    ($unsuspendResult === 'unsuspended' ? 'Isolir berhasil dibuka otomatis.' : ''),
                'unsuspended' => $unsuspendResult === 'unsuspended',
                'callback_data' => $callbackData,
            ]);
        } catch (\Exception $e) {
            Log::error("[DEMO] Payment execution failed: " . $e->getMessage());
            return response()->json(['error' => 'Gagal memproses pembayaran demo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark payment as confirmed (manual confirmation with proof upload)
     */
    public function confirmManual(Request $request, CustomerPayment $payment)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer || $payment->customer_id !== $customer->id) {
            return response()->json(['error' => 'Tidak ditemukan'], 404);
        }
        
        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Pembayaran tidak dalam status pending'], 422);
        }
        
        $request->validate([
            'proof' => 'required|string', // Base64 image of transfer proof
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Save proof image
        $proofPath = null;
        $image = $request->proof;
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);
            $image = base64_decode($image);
            
            $filename = 'proof_' . time() . '.' . $type;
            \Illuminate\Support\Facades\Storage::put('public/payments/' . $filename, $image);
            $proofPath = $filename;
        }
        
        $payment->update([
            'status' => 'verifying',
            'payment_proof' => $proofPath,
            'notes' => $request->notes,
            'paid_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi admin.',
        ]);
    }

    /**
     * Cancel pending payment
     */
    public function cancel(CustomerPayment $payment)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer || $payment->customer_id !== $customer->id) {
            return response()->json(['error' => 'Tidak ditemukan'], 404);
        }
        
        if (!in_array($payment->status, ['pending', 'verifying'])) {
            return response()->json(['error' => 'Pembayaran tidak dapat dibatalkan'], 422);
        }
        
        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dibatalkan',
        ]);
    }
}
