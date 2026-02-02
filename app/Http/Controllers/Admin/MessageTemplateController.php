<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\PopSetting;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MessageTemplateController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:message-templates.view', only: ['index', 'show']),
            new Middleware('permission:message-templates.edit', only: ['edit', 'update']),
            new Middleware('permission:message-templates.reset', only: ['resetToDefault']),
            new Middleware('permission:message-templates.preview', only: ['preview']),
            new Middleware('permission:message-templates.send-test', only: ['sendTest']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Display list of message templates
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? null : $user->id;

        // Get POP settings for SMTP/WA config status
        $popSetting = null;
        if ($popId) {
            $popSetting = PopSetting::where('user_id', $popId)->first();
        }

        // Get all template codes
        $templateCodes = MessageTemplate::templateCodes();
        
        // Filter by channel
        $channel = $request->get('channel', 'email');
        
        // Get existing templates for this POP
        $existingTemplates = MessageTemplate::where(function($q) use ($popId) {
                if ($popId) {
                    $q->where('pop_id', $popId)->orWhereNull('pop_id');
                } else {
                    $q->whereNull('pop_id');
                }
            })
            ->where('channel', $channel)
            ->get()
            ->keyBy('code');

        // Build template list with status
        $templates = [];
        foreach ($templateCodes as $code => $info) {
            $template = $existingTemplates->get($code);
            $defaultTemplate = $template && $template->pop_id === null ? $template : 
                MessageTemplate::where('code', $code)
                    ->where('channel', $channel)
                    ->whereNull('pop_id')
                    ->first();
            
            $templates[] = [
                'code' => $code,
                'name' => $info['name'],
                'description' => $info['description'],
                'template' => $template,
                'default_template' => $defaultTemplate,
                'has_custom' => $template && $template->pop_id !== null,
                'is_active' => $template ? $template->is_active : ($defaultTemplate ? $defaultTemplate->is_active : false),
            ];
        }

        return view('admin.message-templates.index', compact('templates', 'channel', 'popSetting', 'popId'));
    }

    /**
     * Show edit form for a template
     */
    public function edit(Request $request, string $code)
    {
        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? null : $user->id;
        $channel = $request->get('channel', 'email');

        // Get template info
        $templateInfo = MessageTemplate::getTemplateInfo($code);
        if (!$templateInfo) {
            return redirect()->route('admin.message-templates.index')
                ->with('error', 'Template tidak ditemukan');
        }

        // Get existing template or create new
        $template = MessageTemplate::where('code', $code)
            ->where('channel', $channel)
            ->where(function($q) use ($popId) {
                if ($popId) {
                    $q->where('pop_id', $popId);
                } else {
                    $q->whereNull('pop_id');
                }
            })
            ->first();

        // If no custom template, get default
        $defaultTemplate = MessageTemplate::where('code', $code)
            ->where('channel', $channel)
            ->whereNull('pop_id')
            ->where('is_default', true)
            ->first();

        // Get POP settings for preview
        $popSetting = PopSetting::where('user_id', $popId ?? auth()->id())->first();

        return view('admin.message-templates.edit', compact(
            'code', 
            'channel', 
            'templateInfo', 
            'template', 
            'defaultTemplate',
            'popSetting',
            'popId'
        ));
    }

    /**
     * Update or create template
     */
    public function update(Request $request, string $code)
    {
        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? null : $user->id;
        $channel = $request->input('channel', 'email');

        $rules = [
            'channel' => 'required|in:email,whatsapp,sms',
            'is_active' => 'boolean',
        ];

        if ($channel === 'email') {
            $rules['email_subject'] = 'required|string|max:255';
            $rules['email_body'] = 'required|string';
        } else {
            $rules['wa_body'] = 'required|string';
        }

        $request->validate($rules);

        $templateInfo = MessageTemplate::getTemplateInfo($code);
        if (!$templateInfo) {
            return response()->json(['success' => false, 'message' => 'Template tidak ditemukan'], 404);
        }

        // Find or create template
        $template = MessageTemplate::updateOrCreate(
            [
                'code' => $code,
                'channel' => $channel,
                'pop_id' => $popId,
            ],
            [
                'name' => $templateInfo['name'],
                'description' => $templateInfo['description'],
                'email_subject' => $request->input('email_subject'),
                'email_body' => $request->input('email_body'),
                'wa_body' => $request->input('wa_body'),
                'is_active' => $request->boolean('is_active', true),
                'available_variables' => $templateInfo['variables'],
            ]
        );

        $this->activityLog->log('update', 'message_templates', "Updated template: {$templateInfo['name']} ({$channel})");

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Template berhasil disimpan!',
            ]);
        }

        return redirect()->route('admin.message-templates.index', ['channel' => $channel])
            ->with('success', 'Template berhasil disimpan!');
    }

    /**
     * Reset template to default
     */
    public function resetToDefault(Request $request, string $code)
    {
        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? null : $user->id;
        $channel = $request->input('channel', 'email');

        // Only non-superadmin can reset (delete their custom template)
        if ($popId) {
            MessageTemplate::where('code', $code)
                ->where('channel', $channel)
                ->where('pop_id', $popId)
                ->delete();

            $this->activityLog->log('delete', 'message_templates', "Reset template to default: {$code} ({$channel})");

            return response()->json([
                'success' => true,
                'message' => 'Template direset ke default!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Superadmin tidak bisa reset template default',
        ], 400);
    }

    /**
     * Preview template with sample data
     */
    public function preview(Request $request)
    {
        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? null : $user->id;
        
        $subject = $request->input('subject', '');
        $body = $request->input('body', '');
        $channel = $request->input('channel', 'email');

        // Get POP settings for sample data
        $popSetting = PopSetting::where('user_id', $popId ?? $user->id)->first();

        // Sample variables
        $sampleData = [
            'customer_name' => 'John Doe',
            'customer_id' => 'POP1123456',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'package_name' => 'Paket 10 Mbps',
            'package_price' => 'Rp 150.000',
            'password' => '12345',
            'isp_name' => $popSetting->isp_name ?? 'Internet35',
            'isp_phone' => $popSetting->isp_phone ?? '08001234567',
            'isp_email' => $popSetting->isp_email ?? 'support@internet35.id',
            'isp_address' => $popSetting->isp_address ?? 'Jl. Contoh No. 123',
            'login_url' => url('/login'),
            'password' => 'TempPass123',
            'reset_url' => url('/password/reset/sample-token'),
            'expire_minutes' => '60',
            'invoice_number' => 'INV-2026-0001',
            'invoice_date' => now()->format('d F Y'),
            'due_date' => now()->addDays(7)->format('d F Y'),
            'amount' => 'Rp 150.000',
            'period' => 'Februari 2026',
            'payment_url' => url('/pelanggan/invoices'),
            'bank_accounts' => 'BCA: 1234567890 (a.n. PT Internet35)',
            'days_left' => '3',
            'days_overdue' => '5',
            'late_fee' => 'Rp 10.000',
            'total_amount' => 'Rp 160.000',
            'payment_date' => now()->format('d F Y'),
            'payment_method' => 'Transfer Bank BCA',
            'active_until' => now()->addMonth()->format('d F Y'),
            'isolate_reason' => 'Tagihan belum dibayar',
            'expired_date' => now()->format('d F Y'),
            'renewal_url' => url('/pelanggan/invoices'),
        ];

        // Replace variables in subject and body
        foreach ($sampleData as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return response()->json([
            'success' => true,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Send test email/WA
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:email,whatsapp',
            'recipient' => 'required|string',
            'subject' => 'nullable|string',
            'body' => 'required|string',
        ]);

        $user = auth()->user();
        $popId = $user->hasRole('superadmin') ? null : $user->id;
        $popSetting = PopSetting::where('user_id', $popId ?? $user->id)->first();

        if ($request->channel === 'email') {
            // Check SMTP settings
            if (!$popSetting || !$popSetting->smtp_host) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP belum dikonfigurasi. Silakan setup di Pengaturan SMTP.',
                ], 400);
            }

            try {
                // Use the NotificationService to send test email
                $notificationService = app(\App\Services\NotificationService::class);
                $result = $notificationService->sendEmail(
                    $request->recipient,
                    $request->subject ?? 'Test Email',
                    $request->body,
                    $popSetting
                );

                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Email test berhasil dikirim ke ' . $request->recipient,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengirim email: ' . $result['message'],
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            // WhatsApp - check WA settings
            if (!$popSetting || !$popSetting->wa_api_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp API belum dikonfigurasi. Silakan setup di Pengaturan WhatsApp.',
                ], 400);
            }

            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $result = $notificationService->sendWhatsApp(
                    $request->recipient,
                    $request->body,
                    $popSetting
                );

                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'WhatsApp test berhasil dikirim ke ' . $request->recipient,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mengirim WhatsApp: ' . $result['message'],
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }
        }
    }
}
