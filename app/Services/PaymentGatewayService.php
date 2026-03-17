<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\PaymentGateway;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    /**
     * Create a payment transaction
     * In demo mode: simulate locally without API calls
     * In live mode: call actual payment provider API
     *
     * @return array ['success' => bool, 'payment_url' => string|null, 'message' => string, 'data' => array]
     */
    public function createTransaction(PaymentGateway $gateway, CustomerPayment $payment, Customer $customer): array
    {
        if ($gateway->mode === 'demo') {
            return $this->createDemoTransaction($gateway, $payment, $customer);
        }

        return $this->createLiveTransaction($gateway, $payment, $customer);
    }

    /**
     * Demo mode: simulate payment locally
     * Generate fake response that looks like real gateway response
     */
    protected function createDemoTransaction(PaymentGateway $gateway, CustomerPayment $payment, Customer $customer): array
    {
        $fakeExternalId = 'DEMO-' . strtoupper(Str::random(16));
        $fakeReference = 'REF-DEMO-' . time();

        // Generate realistic fake response per gateway type
        $fakeResponse = $this->generateDemoResponse($gateway, $payment, $fakeExternalId, $fakeReference);

        // Update payment with demo data
        $payment->update([
            'external_id' => $fakeExternalId,
            'external_reference' => $fakeReference,
            'gateway_response' => $fakeResponse,
            'payment_url' => route('pelanggan.payment.demo-process', $payment),
        ]);

        return [
            'success' => true,
            'payment_url' => route('pelanggan.payment.demo-process', $payment),
            'message' => '[DEMO] Transaksi demo berhasil dibuat',
            'data' => $fakeResponse,
            'is_demo' => true,
        ];
    }

    /**
     * Live mode: call actual payment provider API
     */
    protected function createLiveTransaction(PaymentGateway $gateway, CustomerPayment $payment, Customer $customer): array
    {
        $credentials = $gateway->decrypted_credentials;
        if (!$credentials) {
            return [
                'success' => false,
                'payment_url' => null,
                'message' => 'Kredensial gateway belum dikonfigurasi',
                'data' => [],
            ];
        }

        return match ($gateway->gateway_type) {
            'tripay' => $this->createTripayTransaction($gateway, $credentials, $payment, $customer),
            'midtrans' => $this->createMidtransTransaction($gateway, $credentials, $payment, $customer),
            'xendit' => $this->createXenditTransaction($gateway, $credentials, $payment, $customer),
            'duitku' => $this->createDuitkuTransaction($gateway, $credentials, $payment, $customer),
            'ipaymu' => $this->createIpaymuTransaction($gateway, $credentials, $payment, $customer),
            default => [
                'success' => false,
                'payment_url' => null,
                'message' => "Gateway type '{$gateway->gateway_type}' tidak didukung",
                'data' => [],
            ],
        };
    }

    /**
     * Generate realistic demo response per gateway type
     */
    protected function generateDemoResponse(PaymentGateway $gateway, CustomerPayment $payment, string $externalId, string $reference): array
    {
        $amount = (int) $payment->amount;
        $expiredAt = now()->addHours(24)->toIso8601String();

        return match ($gateway->gateway_type) {
            'tripay' => [
                'success' => true,
                'message' => 'SUCCESS',
                'data' => [
                    'reference' => $reference,
                    'merchant_ref' => $payment->payment_number,
                    'payment_selection_type' => 'static',
                    'payment_method' => 'BRIVA',
                    'payment_name' => 'BRI Virtual Account',
                    'customer_name' => $payment->customer?->name ?? 'Demo Customer',
                    'customer_email' => 'demo@example.com',
                    'customer_phone' => '08123456789',
                    'callback_url' => url("/api/webhook/tripay"),
                    'return_url' => url("/payment/success"),
                    'amount' => $amount,
                    'fee_merchant' => 0,
                    'fee_customer' => 4000,
                    'total_fee' => 4000,
                    'amount_received' => $amount,
                    'pay_code' => '1234567890123456',
                    'pay_url' => null,
                    'checkout_url' => route('pelanggan.payment.demo-process', $payment),
                    'status' => 'UNPAID',
                    'expired_time' => now()->addHours(24)->timestamp,
                    'order_items' => [
                        [
                            'name' => 'Pembayaran Internet',
                            'price' => $amount,
                            'quantity' => 1,
                            'subtotal' => $amount,
                        ],
                    ],
                    'instructions' => [
                        [
                            'title' => 'ATM BRI',
                            'steps' => [
                                'Masukkan kartu ATM BRI dan PIN Anda',
                                'Pilih menu Transaksi Lain > Pembayaran > Lainnya > BRIVA',
                                'Masukkan Nomor Virtual Account: 1234567890123456',
                                'Konfirmasi pembayaran Anda',
                            ],
                        ],
                    ],
                    '_demo' => true,
                ],
            ],
            'midtrans' => [
                'status_code' => '201',
                'status_message' => 'Success, transaction is found',
                'transaction_id' => $externalId,
                'order_id' => $payment->payment_number,
                'merchant_id' => 'DEMO_MERCHANT',
                'gross_amount' => number_format($amount, 2, '.', ''),
                'currency' => 'IDR',
                'payment_type' => 'bank_transfer',
                'transaction_time' => now()->format('Y-m-d H:i:s'),
                'transaction_status' => 'pending',
                'fraud_status' => 'accept',
                'redirect_url' => route('pelanggan.payment.demo-process', $payment),
                'va_numbers' => [
                    ['bank' => 'bca', 'va_number' => '1234567890123456'],
                ],
                '_demo' => true,
            ],
            'xendit' => [
                'id' => $externalId,
                'external_id' => $payment->payment_number,
                'user_id' => 'demo_user',
                'status' => 'PENDING',
                'merchant_name' => 'Demo ISP',
                'merchant_profile_picture_url' => null,
                'amount' => $amount,
                'payer_email' => 'demo@example.com',
                'description' => 'Pembayaran Internet #' . $payment->payment_number,
                'invoice_url' => route('pelanggan.payment.demo-process', $payment),
                'expiry_date' => $expiredAt,
                'available_banks' => [
                    ['bank_code' => 'BCA', 'collection_type' => 'POOL', 'bank_account_number' => '1234567890'],
                ],
                'should_exclude_credit_card' => false,
                'should_send_email' => false,
                'created' => now()->toIso8601String(),
                'updated' => now()->toIso8601String(),
                '_demo' => true,
            ],
            'duitku' => [
                'merchantCode' => 'DEMO',
                'reference' => $reference,
                'paymentUrl' => route('pelanggan.payment.demo-process', $payment),
                'vaNumber' => '1234567890123456',
                'amount' => (string) $amount,
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
                '_demo' => true,
            ],
            'ipaymu' => [
                'Status' => 200,
                'Url' => route('pelanggan.payment.demo-process', $payment),
                'TransactionId' => $externalId,
                'SessionId' => Str::uuid()->toString(),
                '_demo' => true,
            ],
            default => [
                'status' => 'pending',
                'external_id' => $externalId,
                'reference' => $reference,
                'amount' => $amount,
                'payment_url' => route('pelanggan.payment.demo-process', $payment),
                '_demo' => true,
            ],
        };
    }

    /**
     * Process demo payment callback (simulate success)
     * Returns data that looks like a real callback from the provider
     */
    public function processDemoCallback(PaymentGateway $gateway, CustomerPayment $payment): array
    {
        return match ($gateway->gateway_type) {
            'tripay' => [
                'reference' => $payment->external_reference,
                'merchant_ref' => $payment->payment_number,
                'payment_selection_type' => 'static',
                'payment_method' => 'BRIVA',
                'payment_name' => 'BRI Virtual Account',
                'customer_name' => $payment->customer?->name ?? 'Demo Customer',
                'customer_email' => 'demo@example.com',
                'customer_phone' => '08123456789',
                'amount' => (int) $payment->amount,
                'fee_merchant' => 0,
                'fee_customer' => 4000,
                'total_fee' => 4000,
                'amount_received' => (int) $payment->amount,
                'is_closed_payment' => 1,
                'status' => 'PAID',
                'paid_at' => now()->timestamp,
                'note' => 'DEMO - Pembayaran simulasi',
                '_demo' => true,
            ],
            'midtrans' => [
                'transaction_time' => now()->format('Y-m-d H:i:s'),
                'transaction_status' => 'settlement',
                'transaction_id' => $payment->external_id,
                'status_message' => 'midtrans payment notification',
                'status_code' => '200',
                'signature_key' => hash('sha512', $payment->external_id . '200' . number_format((int) $payment->amount, 2, '.', '') . 'DEMO_SERVER_KEY'),
                'payment_type' => 'bank_transfer',
                'order_id' => $payment->payment_number,
                'merchant_id' => 'DEMO_MERCHANT',
                'gross_amount' => number_format((int) $payment->amount, 2, '.', ''),
                'fraud_status' => 'accept',
                'currency' => 'IDR',
                '_demo' => true,
            ],
            'xendit' => [
                'id' => $payment->external_id,
                'external_id' => $payment->payment_number,
                'user_id' => 'demo_user',
                'is_high' => false,
                'payment_method' => 'BANK_TRANSFER',
                'status' => 'PAID',
                'merchant_name' => 'Demo ISP',
                'amount' => (int) $payment->amount,
                'paid_amount' => (int) $payment->amount,
                'bank_code' => 'BCA',
                'paid_at' => now()->toIso8601String(),
                'payer_email' => 'demo@example.com',
                'description' => 'Pembayaran Internet #' . $payment->payment_number,
                'created' => $payment->created_at->toIso8601String(),
                'updated' => now()->toIso8601String(),
                'currency' => 'IDR',
                'payment_channel' => 'BCA',
                'payment_destination' => '1234567890',
                '_demo' => true,
            ],
            'duitku' => [
                'merchantCode' => 'DEMO',
                'amount' => (string) (int) $payment->amount,
                'merchantOrderId' => $payment->payment_number,
                'productDetail' => 'Pembayaran Internet',
                'additionalParam' => '',
                'paymentCode' => 'BRIVA',
                'resultCode' => '00',
                'merchantUserId' => $payment->customer_id,
                'reference' => $payment->external_reference,
                'signature' => md5('DEMO' . (int) $payment->amount . $payment->payment_number . 'DEMO_API_KEY'),
                '_demo' => true,
            ],
            'ipaymu' => [
                'trx_id' => $payment->external_id,
                'sid' => Str::uuid()->toString(),
                'status' => 1,
                'status_desc' => 'Pembayaran Berhasil',
                'via' => 'BCA VA',
                'channel' => 'BCA',
                'type' => 'va',
                'amount' => (int) $payment->amount,
                'fee' => 4000,
                'reference_id' => $payment->payment_number,
                '_demo' => true,
            ],
            default => [
                'status' => 'success',
                'external_id' => $payment->external_id,
                'payment_number' => $payment->payment_number,
                'amount' => (int) $payment->amount,
                'paid_at' => now()->toIso8601String(),
                '_demo' => true,
            ],
        };
    }

    // ==========================================
    // LIVE GATEWAY INTEGRATIONS
    // ==========================================

    /**
     * Tripay - Create Transaction
     */
    protected function createTripayTransaction(PaymentGateway $gateway, array $credentials, CustomerPayment $payment, Customer $customer): array
    {
        $merchantCode = $credentials['merchant_code'] ?? '';
        $apiKey = $credentials['api_key'] ?? '';
        $privateKey = $credentials['private_key'] ?? '';

        $baseUrl = $gateway->is_sandbox
            ? 'https://tripay.co.id/api-sandbox'
            : 'https://tripay.co.id/api';

        $amount = (int) $payment->amount;
        $merchantRef = $payment->payment_number;

        $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);

        $data = [
            'method' => 'BRIVA', // Default, can be made configurable
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $customer->name,
            'customer_email' => $customer->user?->email ?? 'noemail@example.com',
            'customer_phone' => $customer->phone ?? '08000000000',
            'order_items' => [
                [
                    'name' => 'Pembayaran Internet #' . ($payment->invoice?->invoice_number ?? $merchantRef),
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
            'callback_url' => url("/api/webhook/tripay"),
            'return_url' => route('pelanggan.payment.confirm', $payment),
            'expired_time' => now()->addHours(24)->timestamp,
            'signature' => $signature,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post("{$baseUrl}/transaction/create", $data);

            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false)) {
                $txData = $result['data'] ?? [];

                $payment->update([
                    'external_id' => $txData['reference'] ?? null,
                    'external_reference' => $txData['reference'] ?? null,
                    'gateway_response' => $result,
                    'payment_url' => $txData['checkout_url'] ?? null,
                    'expired_at' => isset($txData['expired_time']) ? \Carbon\Carbon::createFromTimestamp($txData['expired_time']) : null,
                ]);

                return [
                    'success' => true,
                    'payment_url' => $txData['checkout_url'] ?? null,
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $result,
                ];
            }

            Log::error('Tripay create transaction failed', ['response' => $result]);
            return [
                'success' => false,
                'payment_url' => null,
                'message' => $result['message'] ?? 'Gagal membuat transaksi Tripay',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Tripay exception: ' . $e->getMessage());
            return [
                'success' => false,
                'payment_url' => null,
                'message' => 'Error koneksi ke Tripay: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Midtrans - Create Transaction (Snap)
     */
    protected function createMidtransTransaction(PaymentGateway $gateway, array $credentials, CustomerPayment $payment, Customer $customer): array
    {
        $serverKey = $credentials['server_key'] ?? '';

        $baseUrl = $gateway->is_sandbox
            ? 'https://app.sandbox.midtrans.com/snap/v1'
            : 'https://app.midtrans.com/snap/v1';

        $data = [
            'transaction_details' => [
                'order_id' => $payment->payment_number,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $customer->name,
                'email' => $customer->user?->email ?? 'noemail@example.com',
                'phone' => $customer->phone ?? '08000000000',
            ],
            'callbacks' => [
                'finish' => route('pelanggan.payment.confirm', $payment),
            ],
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->post("{$baseUrl}/transactions", $data);

            $result = $response->json();

            if ($response->successful() && isset($result['redirect_url'])) {
                $payment->update([
                    'external_id' => $result['token'] ?? null,
                    'gateway_response' => $result,
                    'payment_url' => $result['redirect_url'],
                    'expired_at' => now()->addHours(24),
                ]);

                return [
                    'success' => true,
                    'payment_url' => $result['redirect_url'],
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $result,
                ];
            }

            Log::error('Midtrans create transaction failed', ['response' => $result]);
            return [
                'success' => false,
                'payment_url' => null,
                'message' => $result['error_messages'][0] ?? 'Gagal membuat transaksi Midtrans',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans exception: ' . $e->getMessage());
            return [
                'success' => false,
                'payment_url' => null,
                'message' => 'Error koneksi ke Midtrans: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Xendit - Create Invoice
     */
    protected function createXenditTransaction(PaymentGateway $gateway, array $credentials, CustomerPayment $payment, Customer $customer): array
    {
        $secretKey = $credentials['secret_key'] ?? '';

        $baseUrl = 'https://api.xendit.co/v2/invoices';

        $data = [
            'external_id' => $payment->payment_number,
            'amount' => (int) $payment->amount,
            'payer_email' => $customer->user?->email ?? 'noemail@example.com',
            'description' => 'Pembayaran Internet #' . ($payment->invoice?->invoice_number ?? $payment->payment_number),
            'invoice_duration' => 86400, // 24 hours
            'currency' => 'IDR',
            'success_redirect_url' => route('pelanggan.payment.confirm', $payment),
            'failure_redirect_url' => route('pelanggan.invoices'),
        ];

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->post($baseUrl, $data);

            $result = $response->json();

            if ($response->successful() && isset($result['invoice_url'])) {
                $payment->update([
                    'external_id' => $result['id'] ?? null,
                    'external_reference' => $result['id'] ?? null,
                    'gateway_response' => $result,
                    'payment_url' => $result['invoice_url'],
                    'expired_at' => isset($result['expiry_date']) ? \Carbon\Carbon::parse($result['expiry_date']) : null,
                ]);

                return [
                    'success' => true,
                    'payment_url' => $result['invoice_url'],
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $result,
                ];
            }

            Log::error('Xendit create invoice failed', ['response' => $result]);
            return [
                'success' => false,
                'payment_url' => null,
                'message' => $result['message'] ?? 'Gagal membuat transaksi Xendit',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit exception: ' . $e->getMessage());
            return [
                'success' => false,
                'payment_url' => null,
                'message' => 'Error koneksi ke Xendit: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Duitku - Create Transaction
     */
    protected function createDuitkuTransaction(PaymentGateway $gateway, array $credentials, CustomerPayment $payment, Customer $customer): array
    {
        $merchantCode = $credentials['merchant_code'] ?? '';
        $apiKey = $credentials['api_key'] ?? '';

        $baseUrl = $gateway->is_sandbox
            ? 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry'
            : 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry';

        $amount = (int) $payment->amount;
        $merchantOrderId = $payment->payment_number;
        $signature = md5($merchantCode . $merchantOrderId . $amount . $apiKey);

        $data = [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => 'VA', // Default
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Pembayaran Internet #' . ($payment->invoice?->invoice_number ?? $merchantOrderId),
            'customerVaName' => $customer->name,
            'email' => $customer->user?->email ?? 'noemail@example.com',
            'phoneNumber' => $customer->phone ?? '08000000000',
            'callbackUrl' => url("/api/webhook/duitku"),
            'returnUrl' => route('pelanggan.payment.confirm', $payment),
            'expiryPeriod' => 1440, // 24 hours in minutes
            'signature' => $signature,
        ];

        try {
            $response = Http::post($baseUrl, $data);
            $result = $response->json();

            if ($response->successful() && ($result['statusCode'] ?? '') === '00') {
                $payment->update([
                    'external_id' => $result['reference'] ?? null,
                    'external_reference' => $result['reference'] ?? null,
                    'gateway_response' => $result,
                    'payment_url' => $result['paymentUrl'] ?? null,
                    'expired_at' => now()->addMinutes(1440),
                ]);

                return [
                    'success' => true,
                    'payment_url' => $result['paymentUrl'] ?? null,
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $result,
                ];
            }

            Log::error('Duitku create transaction failed', ['response' => $result]);
            return [
                'success' => false,
                'payment_url' => null,
                'message' => $result['statusMessage'] ?? 'Gagal membuat transaksi Duitku',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Duitku exception: ' . $e->getMessage());
            return [
                'success' => false,
                'payment_url' => null,
                'message' => 'Error koneksi ke Duitku: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * iPaymu - Create Transaction
     */
    protected function createIpaymuTransaction(PaymentGateway $gateway, array $credentials, CustomerPayment $payment, Customer $customer): array
    {
        $va = $credentials['virtual_account'] ?? '';
        $apiKey = $credentials['api_key'] ?? '';

        $baseUrl = $gateway->is_sandbox
            ? 'https://sandbox.ipaymu.com/api/v2/payment'
            : 'https://my.ipaymu.com/api/v2/payment';

        $body = [
            'product' => ['Pembayaran Internet #' . ($payment->invoice?->invoice_number ?? $payment->payment_number)],
            'qty' => [1],
            'price' => [(int) $payment->amount],
            'description' => ['Tagihan internet bulanan'],
            'returnUrl' => route('pelanggan.payment.confirm', $payment),
            'notifyUrl' => url("/api/webhook/ipaymu"),
            'cancelUrl' => route('pelanggan.invoices'),
            'referenceId' => $payment->payment_number,
            'buyerName' => $customer->name,
            'buyerPhone' => $customer->phone ?? '08000000000',
            'buyerEmail' => $customer->user?->email ?? 'noemail@example.com',
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = 'POST:' . $va . ':' . $requestBody . ':' . $apiKey;
        $signature = hash_hmac('sha256', $stringToSign, $apiKey);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'va' => $va,
                'signature' => $signature,
                'timestamp' => now()->format('YmdHis'),
            ])->post($baseUrl, $body);

            $result = $response->json();

            if ($response->successful() && ($result['Status'] ?? 0) === 200) {
                $payment->update([
                    'external_id' => $result['Data']['TransactionId'] ?? null,
                    'gateway_response' => $result,
                    'payment_url' => $result['Data']['Url'] ?? null,
                    'expired_at' => now()->addHours(24),
                ]);

                return [
                    'success' => true,
                    'payment_url' => $result['Data']['Url'] ?? null,
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $result,
                ];
            }

            Log::error('iPaymu create transaction failed', ['response' => $result]);
            return [
                'success' => false,
                'payment_url' => null,
                'message' => $result['Message'] ?? 'Gagal membuat transaksi iPaymu',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('iPaymu exception: ' . $e->getMessage());
            return [
                'success' => false,
                'payment_url' => null,
                'message' => 'Error koneksi ke iPaymu: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
