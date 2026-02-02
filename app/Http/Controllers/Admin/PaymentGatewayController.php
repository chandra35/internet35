<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    protected ActivityLogger $activityLog;

    public function __construct(ActivityLogger $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get target user ID (current user for admin-pop, or specified user for superadmin)
     */
    protected function getTargetUserId(?string $userId = null): string
    {
        $user = auth()->user();
        
        if ($userId && $user->hasRole('superadmin')) {
            return $userId;
        }
        
        return $user->id;
    }

    /**
     * Check if SuperAdmin is trying to access without user_id
     */
    protected function requireUserIdForSuperAdmin(Request $request): bool
    {
        $user = auth()->user();
        $userId = $request->query('user_id');
        
        return $user->hasRole('superadmin') && !$userId;
    }

    /**
     * Show payment gateways list
     */
    public function index(Request $request)
    {
        // SuperAdmin without user_id should go to monitoring
        if ($this->requireUserIdForSuperAdmin($request)) {
            return redirect()->route('admin.pop-settings.monitoring')
                ->with('info', 'Silakan pilih Admin POP yang ingin Anda kelola.');
        }
        
        $userId = $request->query('user_id');
        $targetUserId = $this->getTargetUserId($userId);
        
        $gateways = PaymentGateway::where('user_id', $targetUserId)
            ->orderBy('sort_order')
            ->get();
        
        $availableTypes = PaymentGateway::gatewayTypes();
        $existingTypes = $gateways->pluck('gateway_type')->toArray();
        $newTypes = array_diff($availableTypes, $existingTypes);
        
        // For superadmin
        $popUsers = null;
        if (auth()->user()->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        return view('admin.pop-settings.payment-gateways', compact(
            'gateways', 
            'availableTypes', 
            'newTypes', 
            'popUsers', 
            'userId'
        ));
    }

    /**
     * Show create gateway form
     */
    public function create(Request $request)
    {
        $gatewayType = $request->query('type');
        $userId = $request->query('user_id');
        
        if (!$gatewayType || !in_array($gatewayType, PaymentGateway::gatewayTypes())) {
            return response()->json([
                'success' => false,
                'message' => 'Tipe gateway tidak valid!',
            ], 400);
        }

        $targetUserId = $this->getTargetUserId($userId);
        
        // Check if already exists (include soft deleted)
        $existingGateway = PaymentGateway::withTrashed()
            ->where('user_id', $targetUserId)
            ->where('gateway_type', $gatewayType)
            ->first();
            
        if ($existingGateway) {
            if ($existingGateway->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gateway pernah dikonfigurasi dan sudah dihapus. Silakan restore terlebih dahulu!',
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Gateway sudah dikonfigurasi!',
            ], 400);
        }

        $credentialFields = PaymentGateway::credentialFields($gatewayType);
        $requiredDocs = PaymentGateway::requiredDocuments($gatewayType);
        $gatewayLabel = PaymentGateway::gatewayLabels()[$gatewayType];
        $gatewayDescription = PaymentGateway::gatewayDescriptions()[$gatewayType];
        
        return response()->json([
            'success' => true,
            'html' => view('admin.pop-settings.partials.gateway-form', compact(
                'gatewayType',
                'credentialFields',
                'requiredDocs',
                'gatewayLabel',
                'gatewayDescription',
                'userId'
            ))->render(),
        ]);
    }

    /**
     * Store new gateway
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'gateway_type' => 'required|string|in:' . implode(',', PaymentGateway::gatewayTypes()),
            'gateway_name' => 'nullable|string|max:100',
            'is_sandbox' => 'boolean',
            'credentials' => 'required|array',
            'fee_paid_by_customer' => 'boolean',
            'additional_fee' => 'nullable|numeric|min:0',
            'additional_fee_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $targetUserId = $this->getTargetUserId($request->user_id);

        // Check if already exists (include soft deleted)
        $existingGateway = PaymentGateway::withTrashed()
            ->where('user_id', $targetUserId)
            ->where('gateway_type', $request->gateway_type)
            ->first();
            
        if ($existingGateway) {
            if ($existingGateway->trashed()) {
                // Restore soft deleted gateway
                $existingGateway->restore();
                return response()->json([
                    'success' => true,
                    'message' => 'Gateway yang terhapus berhasil direstore! Silakan update konfigurasinya.',
                    'data' => $existingGateway,
                    'restored' => true,
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Gateway sudah dikonfigurasi!',
            ], 400);
        }

        // Determine sandbox status
        $sandboxStatus = 'not_required';
        if (in_array($request->gateway_type, ['duitku'])) {
            $sandboxStatus = 'not_submitted';
        }

        $gateway = PaymentGateway::create([
            'user_id' => $targetUserId,
            'gateway_type' => $request->gateway_type,
            'gateway_name' => $request->gateway_name,
            'is_sandbox' => $request->boolean('is_sandbox', true),
            'is_active' => false,
            'credentials' => $request->credentials,
            'fee_paid_by_customer' => $request->boolean('fee_paid_by_customer', true),
            'additional_fee' => $request->additional_fee ?? 0,
            'additional_fee_percentage' => $request->additional_fee_percentage ?? 0,
            'sandbox_status' => $sandboxStatus,
            'webhook_url' => url("/api/webhook/{$request->gateway_type}"),
            'callback_url' => url("/api/callback/{$request->gateway_type}"),
            'return_url' => url("/payment/success"),
            'cancel_url' => url("/payment/cancel"),
        ]);

        $this->activityLog->logCreate('payment_gateways', "Added payment gateway: {$request->gateway_type}");

        return response()->json([
            'success' => true,
            'message' => 'Payment gateway berhasil ditambahkan!',
        ]);
    }

    /**
     * Show edit gateway form
     */
    public function edit(PaymentGateway $gateway)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        $credentialFields = PaymentGateway::credentialFields($gateway->gateway_type);
        $requiredDocs = PaymentGateway::requiredDocuments($gateway->gateway_type);
        $gatewayLabel = PaymentGateway::gatewayLabels()[$gateway->gateway_type];
        $credentials = $gateway->decrypted_credentials ?? [];
        
        return response()->json([
            'success' => true,
            'html' => view('admin.pop-settings.partials.gateway-form', compact(
                'gateway',
                'credentialFields',
                'requiredDocs',
                'gatewayLabel',
                'credentials'
            ))->render(),
        ]);
    }

    /**
     * Update gateway
     */
    public function update(Request $request, PaymentGateway $gateway)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'gateway_name' => 'nullable|string|max:100',
            'is_sandbox' => 'boolean',
            'credentials' => 'required|array',
            'fee_paid_by_customer' => 'boolean',
            'additional_fee' => 'nullable|numeric|min:0',
            'additional_fee_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $oldData = $gateway->toArray();

        $gateway->gateway_name = $request->gateway_name;
        $gateway->is_sandbox = $request->boolean('is_sandbox', true);
        $gateway->credentials = $request->credentials;
        $gateway->fee_paid_by_customer = $request->boolean('fee_paid_by_customer', true);
        $gateway->additional_fee = $request->additional_fee ?? 0;
        $gateway->additional_fee_percentage = $request->additional_fee_percentage ?? 0;
        $gateway->save();

        $this->activityLog->logUpdate('payment_gateways', "Updated payment gateway: {$gateway->gateway_type}", $oldData, $gateway->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Payment gateway berhasil diupdate!',
        ]);
    }

    /**
     * Toggle gateway active status
     */
    public function toggleActive(PaymentGateway $gateway)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        if (!$gateway->is_active && !$gateway->canEnable()) {
            return response()->json([
                'success' => false,
                'message' => 'Gateway tidak dapat diaktifkan. Pastikan kredensial sudah diisi dan verifikasi (jika diperlukan) sudah selesai.',
            ], 400);
        }

        $gateway->is_active = !$gateway->is_active;
        $gateway->save();

        $status = $gateway->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->activityLog->logUpdate('payment_gateways', "Gateway {$gateway->gateway_type} {$status}");

        return response()->json([
            'success' => true,
            'message' => "Payment gateway berhasil {$status}!",
            'is_active' => $gateway->is_active,
        ]);
    }

    /**
     * Test gateway connection
     */
    public function testConnection(PaymentGateway $gateway)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        $credentials = $gateway->decrypted_credentials;
        if (!$credentials) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial belum dikonfigurasi!',
            ]);
        }

        // Test connection based on gateway type
        try {
            $result = $this->performConnectionTest($gateway);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Perform actual connection test
     */
    protected function performConnectionTest(PaymentGateway $gateway): array
    {
        $credentials = $gateway->decrypted_credentials;
        $baseUrl = $gateway->is_sandbox ? $this->getSandboxUrl($gateway->gateway_type) : $this->getProductionUrl($gateway->gateway_type);

        return match ($gateway->gateway_type) {
            'midtrans' => $this->testMidtrans($credentials, $gateway->is_sandbox),
            'duitku' => $this->testDuitku($credentials, $gateway->is_sandbox),
            'xendit' => $this->testXendit($credentials, $gateway->is_sandbox),
            'tripay' => $this->testTripay($credentials, $gateway->is_sandbox),
            'ipaymu' => $this->testIpaymu($credentials, $gateway->is_sandbox),
            default => ['success' => false, 'message' => 'Gateway tidak didukung'],
        };
    }

    protected function testMidtrans(array $credentials, bool $sandbox): array
    {
        $baseUrl = $sandbox ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
        
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($credentials['server_key'], '')
            ->get("{$baseUrl}/v2/point_inquiry/{$credentials['merchant_id']}");

        // Even if it returns 404 (no points), it means credentials are valid
        if ($response->status() === 404 || $response->successful()) {
            return ['success' => true, 'message' => 'Koneksi berhasil!'];
        }

        return ['success' => false, 'message' => 'Kredensial tidak valid atau server error'];
    }

    protected function testDuitku(array $credentials, bool $sandbox): array
    {
        $baseUrl = $sandbox ? 'https://sandbox.duitku.com/webapi/api/merchant' : 'https://passport.duitku.com/webapi/api/merchant';
        
        $params = [
            'merchantcode' => $credentials['merchant_code'],
            'datetime' => date('Y-m-d H:i:s'),
        ];
        $params['signature'] = md5($credentials['merchant_code'] . $params['datetime'] . $credentials['api_key']);
        
        $response = \Illuminate\Support\Facades\Http::asForm()
            ->post("{$baseUrl}/paymentmethod/getpaymentmethod", $params);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['paymentFee'])) {
                return ['success' => true, 'message' => 'Koneksi berhasil!', 'data' => $data];
            }
        }

        return ['success' => false, 'message' => $response->json()['Message'] ?? 'Koneksi gagal'];
    }

    protected function testXendit(array $credentials, bool $sandbox): array
    {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($credentials['secret_key'], '')
            ->get('https://api.xendit.co/balance');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Koneksi berhasil!', 'data' => $response->json()];
        }

        return ['success' => false, 'message' => 'Kredensial tidak valid'];
    }

    protected function testTripay(array $credentials, bool $sandbox): array
    {
        $baseUrl = $sandbox ? 'https://tripay.co.id/api-sandbox' : 'https://tripay.co.id/api';
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $credentials['api_key'],
        ])->get("{$baseUrl}/merchant/payment-channel");

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Koneksi berhasil!'];
        }

        return ['success' => false, 'message' => 'Kredensial tidak valid'];
    }

    protected function testIpaymu(array $credentials, bool $sandbox): array
    {
        $baseUrl = $sandbox ? 'https://sandbox.ipaymu.com/api/v2' : 'https://my.ipaymu.com/api/v2';
        
        $body = ['account' => $credentials['virtual_account']];
        $signature = hash_hmac('sha256', json_encode($body), $credentials['api_key']);
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'va' => $credentials['virtual_account'],
            'signature' => $signature,
            'timestamp' => time(),
        ])->post("{$baseUrl}/balance", $body);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Koneksi berhasil!'];
        }

        return ['success' => false, 'message' => 'Kredensial tidak valid'];
    }

    protected function getSandboxUrl(string $type): string
    {
        return match ($type) {
            'midtrans' => 'https://api.sandbox.midtrans.com',
            'duitku' => 'https://sandbox.duitku.com',
            'xendit' => 'https://api.xendit.co',
            'tripay' => 'https://tripay.co.id/api-sandbox',
            'ipaymu' => 'https://sandbox.ipaymu.com',
            default => '',
        };
    }

    protected function getProductionUrl(string $type): string
    {
        return match ($type) {
            'midtrans' => 'https://api.midtrans.com',
            'duitku' => 'https://passport.duitku.com',
            'xendit' => 'https://api.xendit.co',
            'tripay' => 'https://tripay.co.id/api',
            'ipaymu' => 'https://my.ipaymu.com',
            default => '',
        };
    }

    /**
     * Delete gateway
     */
    public function destroy(PaymentGateway $gateway)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        $name = $gateway->display_name;
        $gateway->delete();

        $this->activityLog->logDelete('payment_gateways', "Deleted payment gateway: {$name}");

        return response()->json([
            'success' => true,
            'message' => 'Payment gateway berhasil dihapus!',
        ]);
    }

    /**
     * Submit sandbox verification request (for Duitku)
     */
    public function submitSandboxRequest(Request $request, PaymentGateway $gateway)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        if (!$gateway->requiresSandboxVerification()) {
            return response()->json([
                'success' => false,
                'message' => 'Gateway ini tidak memerlukan verifikasi sandbox!',
            ], 400);
        }

        $request->validate([
            'verification_notes' => 'nullable|string|max:1000',
            'documents' => 'required|array',
            'documents.ktp' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.npwp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.akta' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.nib' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.screenshot' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        // Upload documents
        $documents = [];
        foreach ($request->file('documents') as $key => $file) {
            $path = $file->store("verification-docs/{$gateway->id}", 'private');
            $documents[$key] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        $gateway->verification_documents = $documents;
        $gateway->verification_notes = $request->verification_notes;
        $gateway->sandbox_status = 'pending';
        $gateway->sandbox_request_date = now();
        $gateway->save();

        $this->activityLog->logUpdate('payment_gateways', "Submitted sandbox verification for: {$gateway->gateway_type}");

        // TODO: Send notification to superadmin

        return response()->json([
            'success' => true,
            'message' => 'Permohonan verifikasi sandbox berhasil dikirim! Mohon tunggu proses review dari admin.',
        ]);
    }

    /**
     * Review sandbox request (superadmin only)
     */
    public function reviewSandboxRequest(Request $request, PaymentGateway $gateway)
    {
        $user = auth()->user();
        if (!$user->hasRole('superadmin')) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:1000',
        ]);

        if ($request->action === 'approve') {
            $gateway->sandbox_status = 'approved';
            $gateway->sandbox_approved_date = now();
            $gateway->sandbox_reviewed_by = $user->id;
            $message = 'Permohonan sandbox berhasil disetujui!';
        } else {
            $gateway->sandbox_status = 'rejected';
            $gateway->sandbox_rejection_reason = $request->rejection_reason;
            $gateway->sandbox_reviewed_by = $user->id;
            $message = 'Permohonan sandbox ditolak!';
        }

        $gateway->save();

        $this->activityLog->logUpdate('payment_gateways', "Reviewed sandbox request for gateway ID: {$gateway->id}, action: {$request->action}");

        // TODO: Send notification to user

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * List pending sandbox requests (superadmin only)
     */
    public function pendingSandboxRequests()
    {
        $user = auth()->user();
        if (!$user->hasRole('superadmin')) {
            abort(403);
        }

        $pendingRequests = PaymentGateway::with('user')
            ->whereIn('sandbox_status', ['pending', 'in_review'])
            ->orderBy('sandbox_request_date', 'asc')
            ->get();

        $processedRequests = PaymentGateway::with('user')
            ->whereIn('sandbox_status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return view('admin.pop-settings.sandbox-requests', compact('pendingRequests', 'processedRequests'));
    }

    /**
     * Download verification document
     */
    public function downloadDocument(PaymentGateway $gateway, string $docKey)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user->hasRole('superadmin') && $gateway->user_id !== $user->id) {
            abort(403);
        }

        $documents = $gateway->verification_documents;
        if (!isset($documents[$docKey])) {
            abort(404);
        }

        $doc = $documents[$docKey];
        return Storage::disk('private')->download($doc['path'], $doc['original_name']);
    }
}
