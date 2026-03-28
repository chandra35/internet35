<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Services\CustomerUnsuspendService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected CustomerUnsuspendService $unsuspendService;

    public function __construct(CustomerUnsuspendService $unsuspendService)
    {
        $this->unsuspendService = $unsuspendService;
    }

    /**
     * Handle Tripay webhook callback
     */
    public function tripay(Request $request)
    {
        Log::info('Tripay webhook received', $request->all());

        $callbackSignature = $request->server('HTTP_X_CALLBACK_SIGNATURE');
        $merchantRef = $request->input('merchant_ref');
        $status = $request->input('status');

        if (!$merchantRef) {
            return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $payment = CustomerPayment::where('payment_number', $merchantRef)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $gateway = $payment->paymentGateway;
        if (!$gateway) {
            return response()->json(['success' => false, 'message' => 'Gateway not found'], 404);
        }

        // Verify signature (skip in demo mode)
        if ($gateway->mode !== 'demo') {
            $credentials = $gateway->decrypted_credentials;
            $privateKey = $credentials['private_key'] ?? '';
            $json = $request->getContent();
            $expectedSignature = hash_hmac('sha256', $json, $privateKey);

            if ($callbackSignature !== $expectedSignature) {
                Log::warning('Tripay webhook: invalid signature', ['merchant_ref' => $merchantRef]);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }
        }

        // Process payment status
        $this->processPaymentStatus($payment, $status, $request->all());

        return response()->json(['success' => true]);
    }

    /**
     * Handle Midtrans webhook notification
     */
    public function midtrans(Request $request)
    {
        Log::info('Midtrans webhook received', $request->all());

        $orderId = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');
        $signatureKey = $request->input('signature_key');

        if (!$orderId) {
            return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $payment = CustomerPayment::where('payment_number', $orderId)->first();
        if (!$payment) {
            // Return 200 for Midtrans test notifications (order_id starts with "test-conn-")
            if (str_starts_with($orderId, 'test-conn-')) {
                Log::info('Midtrans test connection received', ['order_id' => $orderId]);
                return response()->json(['success' => true, 'message' => 'Test connection OK']);
            }
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $gateway = $payment->paymentGateway;
        if (!$gateway) {
            return response()->json(['success' => false, 'message' => 'Gateway not found'], 404);
        }

        // Verify signature (skip in demo mode)
        if ($gateway->mode !== 'demo') {
            $credentials = $gateway->decrypted_credentials;
            $serverKey = $credentials['server_key'] ?? '';
            $statusCode = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');
            $transactionId = $request->input('transaction_id');

            $expectedSignature = hash('sha512', $transactionId . $statusCode . $grossAmount . $serverKey);

            if ($signatureKey !== $expectedSignature) {
                Log::warning('Midtrans webhook: invalid signature', ['order_id' => $orderId]);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }
        }

        // Map Midtrans status to our status
        $status = match ($transactionStatus) {
            'capture', 'settlement' => 'PAID',
            'pending' => 'UNPAID',
            'deny', 'cancel' => 'FAILED',
            'expire' => 'EXPIRED',
            'refund', 'partial_refund' => 'REFUND',
            default => $transactionStatus,
        };

        $this->processPaymentStatus($payment, $status, $request->all());

        return response()->json(['success' => true]);
    }

    /**
     * Handle Xendit webhook callback
     */
    public function xendit(Request $request)
    {
        Log::info('Xendit webhook received', $request->all());

        $externalId = $request->input('external_id');
        $status = $request->input('status');
        $callbackToken = $request->server('HTTP_X_CALLBACK_TOKEN');

        if (!$externalId) {
            return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $payment = CustomerPayment::where('payment_number', $externalId)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $gateway = $payment->paymentGateway;
        if (!$gateway) {
            return response()->json(['success' => false, 'message' => 'Gateway not found'], 404);
        }

        // Verify callback token (skip in demo mode)
        if ($gateway->mode !== 'demo') {
            $credentials = $gateway->decrypted_credentials;
            $webhookToken = $credentials['webhook_token'] ?? '';

            if ($webhookToken && $callbackToken !== $webhookToken) {
                Log::warning('Xendit webhook: invalid token', ['external_id' => $externalId]);
                return response()->json(['success' => false, 'message' => 'Invalid token'], 403);
            }
        }

        $this->processPaymentStatus($payment, $status, $request->all());

        return response()->json(['success' => true]);
    }

    /**
     * Handle Duitku webhook callback
     */
    public function duitku(Request $request)
    {
        Log::info('Duitku webhook received', $request->all());

        $merchantOrderId = $request->input('merchantOrderId');
        $resultCode = $request->input('resultCode');
        $signature = $request->input('signature');

        if (!$merchantOrderId) {
            return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $payment = CustomerPayment::where('payment_number', $merchantOrderId)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $gateway = $payment->paymentGateway;
        if (!$gateway) {
            return response()->json(['success' => false, 'message' => 'Gateway not found'], 404);
        }

        // Verify signature (skip in demo mode)
        if ($gateway->mode !== 'demo') {
            $credentials = $gateway->decrypted_credentials;
            $merchantCode = $credentials['merchant_code'] ?? '';
            $apiKey = $credentials['api_key'] ?? '';
            $amount = $request->input('amount');

            $expectedSignature = md5($merchantCode . $amount . $merchantOrderId . $apiKey);

            if ($signature !== $expectedSignature) {
                Log::warning('Duitku webhook: invalid signature', ['order_id' => $merchantOrderId]);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }
        }

        // Map Duitku result code
        $status = match ($resultCode) {
            '00' => 'PAID',
            '01' => 'UNPAID',
            '02' => 'FAILED',
            default => 'FAILED',
        };

        $this->processPaymentStatus($payment, $status, $request->all());

        return response()->json(['success' => true]);
    }

    /**
     * Handle iPaymu webhook callback
     */
    public function ipaymu(Request $request)
    {
        Log::info('iPaymu webhook received', $request->all());

        $referenceId = $request->input('reference_id');
        $statusCode = (int) $request->input('status');

        if (!$referenceId) {
            return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $payment = CustomerPayment::where('payment_number', $referenceId)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        // Map iPaymu status
        $status = match ($statusCode) {
            1 => 'PAID',
            0 => 'UNPAID',
            -1 => 'EXPIRED',
            default => 'FAILED',
        };

        $this->processPaymentStatus($payment, $status, $request->all());

        return response()->json(['success' => true]);
    }

    /**
     * Process payment status from webhook
     */
    protected function processPaymentStatus(CustomerPayment $payment, string $status, array $rawData): void
    {
        // Store raw callback data
        $payment->update([
            'gateway_response' => $rawData,
        ]);

        $normalizedStatus = strtoupper($status);

        if (in_array($normalizedStatus, ['PAID', 'SUCCESS', 'SETTLEMENT'])) {
            if ($payment->status !== 'success') {
                $payment->markAsSuccess($rawData['reference'] ?? $rawData['transaction_id'] ?? null);

                // Auto unsuspend (buka isolir) if customer is suspended
                $this->autoUnsuspend($payment);

                // Update gateway statistics
                $this->updateGatewayStats($payment);

                // Send payment success notification
                try {
                    $customer = $payment->customer;
                    if ($customer) {
                        app(NotificationService::class)->sendPaymentSuccess($customer, [
                            'invoice_number' => $payment->invoice?->invoice_number ?? '-',
                            'payment_number' => $payment->payment_number,
                            'amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                            'payment_date' => now()->format('d F Y H:i'),
                            'payment_method' => $payment->payment_method ?? '-',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Webhook: Failed to send payment notification: ' . $e->getMessage());
                }

                Log::info("Payment {$payment->payment_number} marked as success via webhook");
            }
        } elseif (in_array($normalizedStatus, ['FAILED', 'DENY', 'CANCEL'])) {
            $payment->update(['status' => 'failed']);
            Log::info("Payment {$payment->payment_number} marked as failed via webhook");
        } elseif (in_array($normalizedStatus, ['EXPIRED'])) {
            $payment->update(['status' => 'expired']);
            Log::info("Payment {$payment->payment_number} marked as expired via webhook");
        } elseif (in_array($normalizedStatus, ['REFUND', 'PARTIAL_REFUND'])) {
            $payment->update(['status' => 'refunded']);
            Log::info("Payment {$payment->payment_number} marked as refunded via webhook");
        }
    }

    /**
     * Auto unsuspend customer when payment succeeds
     */
    protected function autoUnsuspend(CustomerPayment $payment): void
    {
        try {
            $customer = $payment->customer;
            if (!$customer || $customer->status !== 'suspended') {
                return;
            }

            // Check if all overdue invoices for this customer are now paid
            $hasOverdueInvoices = $customer->invoices()
                ->whereIn('status', ['pending', 'overdue'])
                ->where('due_date', '<', now())
                ->exists();

            if (!$hasOverdueInvoices) {
                $this->unsuspendService->unsuspend($customer);
                Log::info("Auto unsuspend: Customer {$customer->customer_id} unsuspended after payment {$payment->payment_number}");
            } else {
                Log::info("Auto unsuspend skipped: Customer {$customer->customer_id} still has overdue invoices");
            }
        } catch (\Exception $e) {
            Log::error("Auto unsuspend failed for payment {$payment->payment_number}: " . $e->getMessage());
        }
    }

    /**
     * Update gateway transaction statistics
     */
    protected function updateGatewayStats(CustomerPayment $payment): void
    {
        if ($payment->payment_gateway_id) {
            $gateway = $payment->paymentGateway;
            if ($gateway) {
                $gateway->increment('total_transactions');
                $gateway->increment('total_amount', $payment->amount);
                $gateway->update(['last_transaction_at' => now()]);
            }
        }
    }
}
