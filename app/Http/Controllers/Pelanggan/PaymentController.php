<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
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
        
        // Get available payment gateways
        $gateways = PaymentGateway::where('is_active', true)
            ->where('pop_id', $customer->pop_id)
            ->get();
        
        return view('pelanggan.invoice-detail', compact('customer', 'invoice', 'gateways'));
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
                'payment_method' => $gateway->type,
                'amount' => $invoice->total_amount,
                'status' => 'pending',
                'external_id' => 'PAY-' . strtoupper(uniqid()),
            ]);
            
            // Here you would integrate with actual payment gateway
            // For now, we'll return the payment page info
            $paymentUrl = $this->getPaymentUrl($gateway, $payment);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'payment_url' => $paymentUrl,
                'message' => 'Silakan lanjutkan pembayaran',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment URL for gateway
     */
    protected function getPaymentUrl(PaymentGateway $gateway, CustomerPayment $payment): string
    {
        // TODO: Implement actual payment gateway integration
        // This is a placeholder that returns a confirmation page
        
        return route('pelanggan.payment.confirm', $payment);
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
     * Mark payment as confirmed (manual confirmation)
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
