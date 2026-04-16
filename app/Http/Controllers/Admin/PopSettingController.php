<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopSetting;
use App\Models\Router;
use App\Models\User;
use App\Helpers\ActivityLogger;
use App\Helpers\Mikrotik\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PopSettingController extends Controller implements HasMiddleware
{
    protected ActivityLogger $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:pop-settings.view', only: ['index', 'ispInfo', 'invoiceSettings', 'previewInvoice', 'livePreviewInvoice', 'integration', 'getCities', 'getDistricts', 'getVillages']),
            new Middleware('permission:pop-settings.edit', only: ['updateIspInfo', 'updateInvoiceSettings', 'removeLogo', 'updateIntegration', 'testRadiusConnection', 'syncIsolirProfile']),
            new Middleware('role:superadmin', only: ['monitoring', 'viewPopDetail', 'copySettingsForm', 'preview', 'copySettings']),
        ];
    }

    public function __construct(ActivityLogger $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get current user's POP setting or specific user's setting (for superadmin)
     * SuperAdmin MUST provide user_id to edit, otherwise redirect to monitoring
     */
    protected function getPopSetting(?string $userId = null): PopSetting
    {
        $user = auth()->user();
        
        // Superadmin can view/edit any user's settings (but must specify user_id)
        if ($userId && $user->hasRole('superadmin')) {
            $targetUser = User::findOrFail($userId);
            return PopSetting::getOrCreateForUser($targetUser->id);
        }
        
        // Admin-pop can only view/edit their own settings
        return PopSetting::getOrCreateForUser($user->id);
    }

    /**
     * Check if SuperAdmin is trying to access without user_id
     * SuperAdmin should use monitoring page, not create own settings
     */
    protected function requireUserIdForSuperAdmin(Request $request)
    {
        $user = auth()->user();
        $userId = $request->query('user_id');
        
        // If superadmin and no user_id, redirect to monitoring
        if ($user->hasRole('superadmin') && !$userId) {
            return true; // Need redirect
        }
        
        return false;
    }

    /**
     * Show ISP settings form
     */
    public function ispInfo(Request $request)
    {
        // SuperAdmin without user_id should go to monitoring
        if ($this->requireUserIdForSuperAdmin($request)) {
            return redirect()->route('admin.pop-settings.monitoring')
                ->with('info', 'Silakan pilih Admin POP yang ingin Anda kelola.');
        }
        
        $userId = $request->query('user_id');
        $popSetting = $this->getPopSetting($userId);
        $provinces = Province::orderBy('name')->get();
        
        // For superadmin - list all admin-pop users
        $popUsers = null;
        if (auth()->user()->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        return view('admin.pop-settings.isp-info', compact('popSetting', 'provinces', 'popUsers', 'userId'));
    }

    /**
     * Update ISP settings
     */
    public function updateIspInfo(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'isp_name' => 'required|string|max:255',
            'isp_tagline' => 'nullable|string|max:255',
            'isp_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'isp_logo_dark' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'isp_favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg|max:512',
            'pop_name' => 'nullable|string|max:255',
            'pop_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'province_code' => 'nullable|string|max:2',
            'city_code' => 'nullable|string|max:4',
            'district_code' => 'nullable|string|max:7',
            'village_code' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'email_billing' => 'nullable|email|max:255',
            'email_support' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
        ]);

        $popSetting = $this->getPopSetting($request->user_id);
        $oldData = $popSetting->toArray();

        // Handle logo uploads
        if ($request->hasFile('isp_logo')) {
            if ($popSetting->isp_logo) {
                Storage::delete($popSetting->isp_logo);
            }
            $popSetting->isp_logo = $request->file('isp_logo')->store('pop-logos', 'public');
        }

        if ($request->hasFile('isp_logo_dark')) {
            if ($popSetting->isp_logo_dark) {
                Storage::delete($popSetting->isp_logo_dark);
            }
            $popSetting->isp_logo_dark = $request->file('isp_logo_dark')->store('pop-logos', 'public');
        }

        if ($request->hasFile('isp_favicon')) {
            if ($popSetting->isp_favicon) {
                Storage::delete($popSetting->isp_favicon);
            }
            $popSetting->isp_favicon = $request->file('isp_favicon')->store('pop-logos', 'public');
        }

        $popSetting->fill($request->except(['isp_logo', 'isp_logo_dark', 'isp_favicon', 'user_id']));
        $popSetting->save();

        $this->activityLog->logUpdate('pop_settings', "Updated ISP info for: {$popSetting->isp_name}", $oldData, $popSetting->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Informasi ISP berhasil disimpan!',
        ]);
    }

    /**
     * Show invoice settings form
     */
    public function invoiceSettings(Request $request)
    {
        // SuperAdmin without user_id should go to monitoring
        if ($this->requireUserIdForSuperAdmin($request)) {
            return redirect()->route('admin.pop-settings.monitoring')
                ->with('info', 'Silakan pilih Admin POP yang ingin Anda kelola.');
        }
        
        $userId = $request->query('user_id');
        $popSetting = $this->getPopSetting($userId);
        
        // For superadmin
        $popUsers = null;
        if (auth()->user()->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        return view('admin.pop-settings.invoice-settings', compact('popSetting', 'popUsers', 'userId'));
    }

    /**
     * Update invoice settings
     */
    public function updateInvoiceSettings(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'invoice_prefix' => 'required|string|max:20',
            'invoice_due_days' => 'required|integer|min:1|max:90',
            'invoice_notes' => 'nullable|string|max:1000',
            'invoice_footer' => 'nullable|string|max:500',
            'invoice_terms' => 'nullable|string|max:2000',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:100',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_name' => 'nullable|string|max:100',
            'bank_accounts.*.branch' => 'nullable|string|max:100',
            'ppn_enabled' => 'boolean',
            'ppn_percentage' => 'nullable|numeric|min:0|max:100',
            'ppn_method' => 'nullable|in:exclusive,inclusive',
            'ppn_display' => 'nullable|in:separate,included',
            'npwp' => 'nullable|string|max:30',
            'business_name' => 'nullable|string|max:255',
            'nib' => 'nullable|string|max:30',
            'isp_license_number' => 'nullable|string|max:100',
        ]);

        $popSetting = $this->getPopSetting($request->user_id);
        $oldData = $popSetting->toArray();

        // Filter empty bank accounts
        $bankAccounts = collect($request->bank_accounts ?? [])->filter(function ($item) {
            return !empty($item['bank_name']) || !empty($item['account_number']);
        })->values()->toArray();

        $popSetting->fill($request->except(['user_id', 'bank_accounts']));
        $popSetting->bank_accounts = $bankAccounts;
        $popSetting->ppn_enabled = $request->boolean('ppn_enabled');
        $popSetting->save();

        $this->activityLog->logUpdate('pop_settings', "Updated invoice settings", $oldData, $popSetting->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan invoice berhasil disimpan!',
        ]);
    }

    /**
     * Remove logo
     */
    public function removeLogo(Request $request)
    {
        $request->validate([
            'type' => 'required|in:logo,logo_dark,favicon',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);

        $popSetting = $this->getPopSetting($request->user_id);
        
        $field = match ($request->type) {
            'logo' => 'isp_logo',
            'logo_dark' => 'isp_logo_dark',
            'favicon' => 'isp_favicon',
        };

        if ($popSetting->$field) {
            Storage::delete($popSetting->$field);
            $popSetting->$field = null;
            $popSetting->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo berhasil dihapus!',
        ]);
    }

    /**
     * Get cities by province
     */
    public function getCities(string $provinceCode)
    {
        $cities = City::where('province_code', $provinceCode)->orderBy('name')->get();
        return response()->json($cities);
    }

    /**
     * Get districts by city
     */
    public function getDistricts(string $cityCode)
    {
        $districts = District::where('city_code', $cityCode)->orderBy('name')->get();
        return response()->json($districts);
    }

    /**
     * Get villages by district
     */
    public function getVillages(string $districtCode)
    {
        $villages = Village::where('district_code', $districtCode)->orderBy('name')->get();
        return response()->json($villages);
    }

    /**
     * List all POP settings (superadmin only)
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin')) {
            abort(403);
        }

        $popUsers = User::role('admin-pop')
            ->with(['popSetting', 'paymentGateways', 'notificationSetting'])
            ->orderBy('name')
            ->paginate(15);
        
        return view('admin.pop-settings.index', compact('popUsers'));
    }

    /**
     * Copy settings form (superadmin only)
     */
    public function copySettingsForm()
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin')) {
            abort(403);
        }

        $popUsers = User::role('admin-pop')->orderBy('name')->get();
        
        return view('admin.pop-settings.copy-settings', compact('popUsers'));
    }

    /**
     * Preview POP settings (for copy feature)
     */
    public function preview(User $user)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        $popSetting = PopSetting::where('user_id', $user->id)->first();
        
        return response()->json($popSetting ?? [
            'isp_name' => null,
            'pop_name' => null,
            'invoice_prefix' => 'INV',
            'ppn_enabled' => false,
            'ppn_percentage' => 11,
        ]);
    }

    /**
     * Copy settings from one user to another (superadmin only)
     */
    public function copySettings(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya superadmin yang dapat menggunakan fitur ini!',
            ], 403);
        }

        $request->validate([
            'source_user_id' => 'required|uuid|exists:users,id',
            'target_user_id' => 'required|uuid|exists:users,id|different:source_user_id',
            'copy_sections' => 'required|array|min:1',
            'copy_sections.*' => 'in:isp,address,contact,invoice,tax,business,bank,notifications',
        ]);

        $sourceSetting = PopSetting::where('user_id', $request->source_user_id)->first();
        if (!$sourceSetting) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaturan sumber tidak ditemukan!',
            ], 404);
        }

        $targetSetting = PopSetting::getOrCreateForUser($request->target_user_id);
        $oldData = $targetSetting->toArray();
        
        $sections = $request->copy_sections;
        
        // ISP Info (without logo)
        if (in_array('isp', $sections)) {
            $targetSetting->isp_name = $sourceSetting->isp_name;
            $targetSetting->isp_tagline = $sourceSetting->isp_tagline;
            $targetSetting->pop_name = $sourceSetting->pop_name;
            $targetSetting->pop_code = $sourceSetting->pop_code;
        }
        
        // Address
        if (in_array('address', $sections)) {
            $targetSetting->address = $sourceSetting->address;
            $targetSetting->province_code = $sourceSetting->province_code;
            $targetSetting->city_code = $sourceSetting->city_code;
            $targetSetting->district_code = $sourceSetting->district_code;
            $targetSetting->village_code = $sourceSetting->village_code;
            $targetSetting->postal_code = $sourceSetting->postal_code;
            $targetSetting->latitude = $sourceSetting->latitude;
            $targetSetting->longitude = $sourceSetting->longitude;
        }
        
        // Contact
        if (in_array('contact', $sections)) {
            $targetSetting->phone = $sourceSetting->phone;
            $targetSetting->phone_secondary = $sourceSetting->phone_secondary;
            $targetSetting->email = $sourceSetting->email;
            $targetSetting->email_billing = $sourceSetting->email_billing;
            $targetSetting->email_support = $sourceSetting->email_support;
            $targetSetting->website = $sourceSetting->website;
            $targetSetting->whatsapp = $sourceSetting->whatsapp;
            $targetSetting->telegram = $sourceSetting->telegram;
            $targetSetting->instagram = $sourceSetting->instagram;
            $targetSetting->facebook = $sourceSetting->facebook;
        }
        
        // Invoice Settings
        if (in_array('invoice', $sections)) {
            $targetSetting->invoice_prefix = $sourceSetting->invoice_prefix;
            $targetSetting->invoice_due_days = $sourceSetting->invoice_due_days;
            $targetSetting->invoice_notes = $sourceSetting->invoice_notes;
            $targetSetting->invoice_footer = $sourceSetting->invoice_footer;
            $targetSetting->invoice_terms = $sourceSetting->invoice_terms;
        }
        
        // Tax Settings
        if (in_array('tax', $sections)) {
            $targetSetting->ppn_enabled = $sourceSetting->ppn_enabled;
            $targetSetting->ppn_percentage = $sourceSetting->ppn_percentage;
            $targetSetting->ppn_method = $sourceSetting->ppn_method;
            $targetSetting->ppn_display = $sourceSetting->ppn_display;
        }
        
        // Business Info
        if (in_array('business', $sections)) {
            $targetSetting->business_name = $sourceSetting->business_name;
            $targetSetting->npwp = $sourceSetting->npwp;
            $targetSetting->nib = $sourceSetting->nib;
            $targetSetting->isp_license_number = $sourceSetting->isp_license_number;
        }
        
        // Bank Accounts
        if (in_array('bank', $sections)) {
            $targetSetting->bank_accounts = $sourceSetting->bank_accounts;
        }
        
        $targetSetting->save();
        
        // Copy notification templates (without API keys)
        if (in_array('notifications', $sections)) {
            $sourceNotif = \App\Models\NotificationSetting::where('user_id', $request->source_user_id)->first();
            if ($sourceNotif) {
                $targetNotif = \App\Models\NotificationSetting::getOrCreateForUser($request->target_user_id);
                $targetNotif->templates = $sourceNotif->templates;
                $targetNotif->enabled_events = $sourceNotif->enabled_events;
                $targetNotif->save();
            }
        }

        $this->activityLog->logUpdate('pop_settings', "Copied settings from user {$request->source_user_id} to {$request->target_user_id}", $oldData, $targetSetting->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil disalin!',
        ]);
    }

    /**
     * POP Monitoring Dashboard (superadmin only)
     * Shows overview of all POPs with their setup status
     */
    public function monitoring(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin')) {
            abort(403, 'Akses hanya untuk SuperAdmin');
        }

        // Get filter parameters
        $search = $request->query('search');
        $statusFilter = $request->query('status'); // complete, incomplete, all
        $sortBy = $request->query('sort', 'name'); // name, created_at, status
        $sortDir = $request->query('dir', 'asc');

        // Build query
        $query = User::role('admin-pop')
            ->with(['popSetting', 'paymentGateways', 'notificationSetting']);

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('popSetting', function ($q2) use ($search) {
                      $q2->where('isp_name', 'like', "%{$search}%")
                         ->orWhere('pop_name', 'like', "%{$search}%")
                         ->orWhere('pop_code', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        if ($sortBy === 'name') {
            $query->orderBy('name', $sortDir);
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', $sortDir);
        }

        $popUsers = $query->get();

        // Calculate setup status for each POP
        $popData = $popUsers->map(function ($popUser) {
            $popSetting = $popUser->popSetting;
            $paymentGateways = $popUser->paymentGateways;
            $notifSetting = $popUser->notificationSetting;

            // Check each setup section
            $ispComplete = $popSetting && !empty($popSetting->isp_name);
            $invoiceComplete = $popSetting && !empty($popSetting->invoice_prefix);
            $paymentComplete = $paymentGateways->where('is_active', true)->count() > 0;
            $notifComplete = $notifSetting && (
                $notifSetting->email_enabled || 
                $notifSetting->whatsapp_enabled || 
                $notifSetting->telegram_enabled
            );

            $completedSections = collect([
                'isp' => $ispComplete,
                'invoice' => $invoiceComplete,
                'payment' => $paymentComplete,
                'notification' => $notifComplete,
            ]);

            $completedCount = $completedSections->filter()->count();
            $totalSections = 4;
            $progressPercent = round(($completedCount / $totalSections) * 100);

            return [
                'user' => $popUser,
                'pop_setting' => $popSetting,
                'payment_gateways' => $paymentGateways,
                'notification_setting' => $notifSetting,
                'status' => [
                    'isp' => $ispComplete,
                    'invoice' => $invoiceComplete,
                    'payment' => $paymentComplete,
                    'notification' => $notifComplete,
                ],
                'completed_count' => $completedCount,
                'total_sections' => $totalSections,
                'progress_percent' => $progressPercent,
                'is_complete' => $completedCount === $totalSections,
            ];
        });

        // Filter by status
        if ($statusFilter === 'complete') {
            $popData = $popData->filter(fn($item) => $item['is_complete']);
        } elseif ($statusFilter === 'incomplete') {
            $popData = $popData->filter(fn($item) => !$item['is_complete']);
        }

        // Sort by status if requested
        if ($sortBy === 'status') {
            $popData = $popData->sortBy(
                fn($item) => $item['progress_percent'],
                SORT_REGULAR,
                $sortDir === 'desc'
            );
        }

        // Statistics
        $stats = [
            'total_pop' => $popData->count(),
            'complete' => $popData->filter(fn($item) => $item['is_complete'])->count(),
            'incomplete' => $popData->filter(fn($item) => !$item['is_complete'])->count(),
            'avg_progress' => $popData->count() > 0 ? round($popData->avg('progress_percent')) : 0,
        ];

        return view('admin.pop-settings.monitoring', compact('popData', 'stats', 'search', 'statusFilter', 'sortBy', 'sortDir'));
    }

    /**
     * View POP detail (superadmin only, read-only view)
     */
    public function viewPopDetail(User $user)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        if (!$user->hasRole('admin-pop')) {
            abort(404, 'User bukan Admin POP');
        }

        $popSetting = PopSetting::where('user_id', $user->id)->first();
        $paymentGateways = \App\Models\PaymentGateway::where('user_id', $user->id)->get();
        $notifSetting = \App\Models\NotificationSetting::where('user_id', $user->id)->first();

        return view('admin.pop-settings.view-detail', compact('user', 'popSetting', 'paymentGateways', 'notifSetting'));
    }

    /**
     * Show integration settings form (Mikrotik & Radius)
     */
    public function integration(Request $request)
    {
        // SuperAdmin without user_id should go to monitoring
        if ($this->requireUserIdForSuperAdmin($request)) {
            return redirect()->route('admin.pop-settings.monitoring')
                ->with('info', 'Silakan pilih Admin POP yang ingin Anda kelola.');
        }
        
        $userId = $request->query('user_id');
        $popSetting = $this->getPopSetting($userId);
        
        // For superadmin - list all admin-pop users
        $popUsers = null;
        if (auth()->user()->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }
        
        return view('admin.pop-settings.integration', compact('popSetting', 'popUsers', 'userId'));
    }

    /**
     * Update integration settings
     */
    public function updateIntegration(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            // POP Prefix - alphanumeric only (huruf dan angka), tanpa karakter spesial
            'pop_prefix' => 'nullable|string|max:10|alpha_num',
            // Mikrotik Settings
            'mikrotik_sync_enabled' => 'boolean',
            'mikrotik_auto_sync' => 'boolean',
            'isolir_profile_name' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_\-]+$/',
            'isolir_pool_name' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_\-]+$/',
            'isolir_pool_range' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+-[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/'],
            'isolir_local_address' => 'nullable|ip',
            'isolir_dns_server' => 'nullable|ip',
            'isolir_rate_limit' => 'nullable|string|max:50',
            'isolir_redirect_url' => 'nullable|url|max:255',
            // FreeRadius Settings
            'radius_enabled' => 'boolean',
            'radius_host' => 'nullable|required_if:radius_enabled,1|string|max:255',
            'radius_port' => 'nullable|integer|min:1|max:65535',
            'radius_database' => 'nullable|string|max:100',
            'radius_username' => 'nullable|required_if:radius_enabled,1|string|max:100',
            'radius_password' => 'nullable|string|max:255',
            'radius_nas_ip' => 'nullable|ip',
            'radius_nas_secret' => 'nullable|string|max:255',
            'radius_coa_port' => 'nullable|integer|min:1|max:65535',
            'radius_auto_sync' => 'boolean',
        ], [
            'pop_prefix.alpha_num' => 'Prefix hanya boleh berisi huruf dan angka (tanpa spasi, @, titik, atau karakter spesial)',
            'pop_prefix.max' => 'Prefix maksimal 10 karakter',
            'radius_host.required_if' => 'Host Radius wajib diisi jika FreeRadius diaktifkan',
            'radius_username.required_if' => 'Username Radius wajib diisi jika FreeRadius diaktifkan',
        ]);

        $popSetting = $this->getPopSetting($request->user_id);
        $oldData = $popSetting->toArray();

        // Update basic settings
        $popSetting->pop_prefix = $request->pop_prefix ? strtoupper($request->pop_prefix) : null; // Uppercase prefix
        $popSetting->mikrotik_sync_enabled = $request->boolean('mikrotik_sync_enabled');
        $popSetting->mikrotik_auto_sync = $request->boolean('mikrotik_auto_sync');
        $popSetting->isolir_profile_name = $request->isolir_profile_name ?: 'isolir';
        $popSetting->isolir_pool_name = $request->isolir_pool_name ?: 'pool-isolir';
        $popSetting->isolir_pool_range = $request->isolir_pool_range ?: '10.99.0.2-10.99.0.254';
        $popSetting->isolir_local_address = $request->isolir_local_address ?: '10.99.0.1';
        $popSetting->isolir_dns_server = $request->isolir_dns_server;
        $popSetting->isolir_rate_limit = $request->isolir_rate_limit ?: '128k/128k';
        $popSetting->isolir_redirect_url = $request->isolir_redirect_url;
        $popSetting->radius_enabled = $request->boolean('radius_enabled');
        $popSetting->radius_auto_sync = $request->boolean('radius_auto_sync');

        // Update radius settings if provided
        if ($request->radius_enabled) {
            $popSetting->radius_host = $request->radius_host;
            $popSetting->radius_port = $request->radius_port ?? 3306;
            $popSetting->radius_database = $request->radius_database ?? 'radius';
            $popSetting->radius_username = $request->radius_username;
            
            // Only update password if provided
            if ($request->filled('radius_password')) {
                $popSetting->radius_password = $request->radius_password;
            }
            
            $popSetting->radius_nas_ip = $request->radius_nas_ip;
            
            // Only update NAS secret if provided
            if ($request->filled('radius_nas_secret')) {
                $popSetting->radius_nas_secret = $request->radius_nas_secret;
            }
            
            $popSetting->radius_coa_port = $request->radius_coa_port ?? 3799;
        }

        $popSetting->save();

        $this->activityLog->logUpdate('pop_settings', "Updated integration settings", $oldData, $popSetting->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan integrasi berhasil disimpan!',
        ]);
    }

    /**
     * Test Radius connection
     */
    public function testRadiusConnection(Request $request)
    {
        $request->validate([
            'radius_host' => 'required|string',
            'radius_port' => 'required|integer',
            'radius_database' => 'required|string',
            'radius_username' => 'required|string',
            'radius_password' => 'required|string',
        ]);

        try {
            $radiusService = new \App\Services\RadiusService([
                'host' => $request->radius_host,
                'port' => $request->radius_port,
                'database' => $request->radius_database,
                'username' => $request->radius_username,
                'password' => $request->radius_password,
            ]);

            $result = $radiusService->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync isolir setup to all routers for this POP
     * Creates/updates: IP Pool, PPP Profile, Firewall Filter, Firewall NAT
     */
    public function syncIsolirProfile(Request $request)
    {
        $request->validate([
            'profile_name' => 'required|string|max:50|regex:/^[a-zA-Z0-9_\-]+$/',
            'pool_name' => 'required|string|max:50|regex:/^[a-zA-Z0-9_\-]+$/',
            'pool_range' => ['required', 'string', 'max:100', 'regex:/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+-[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/'],
            'local_address' => 'required|ip',
            'dns_server' => 'nullable|ip',
            'rate_limit' => 'required|string|max:50',
            'redirect_url' => 'nullable|url|max:255',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);

        $user = auth()->user();
        $popUserId = $request->user_id && $user->hasRole('superadmin')
            ? $request->user_id
            : $user->id;

        $routers = Router::where('user_id', $popUserId)->where('is_active', true)->get();

        if ($routers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada router aktif yang ditemukan.',
            ], 422);
        }

        $config = [
            'profile_name' => $request->profile_name,
            'pool_name' => $request->pool_name,
            'pool_range' => $request->pool_range,
            'local_address' => $request->local_address,
            'dns_server' => $request->dns_server,
            'rate_limit' => $request->rate_limit,
            'redirect_url' => $request->redirect_url,
        ];

        // Derive subnet from pool range for firewall rules (e.g. 10.99.0.2-10.99.0.254 → 10.99.0.0/24)
        $poolStart = explode('-', $config['pool_range'])[0];
        $octets = explode('.', $poolStart);
        $subnet = $octets[0] . '.' . $octets[1] . '.' . $octets[2] . '.0/24';

        $details = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($routers as $router) {
            $routerDetails = [];

            try {
                $mikrotik = new MikrotikService();

                if (!$mikrotik->connectRouter($router)) {
                    $details[] = ['router' => $router->name, 'success' => false, 'status' => 'Gagal terhubung'];
                    $failCount++;
                    continue;
                }

                // 1. IP Pool — find by comment marker or create
                $existingPool = $mikrotik->findByComment('/ip/pool', '[billing-isolir-pool]');
                if ($existingPool) {
                    $mikrotik->updateIpPool($existingPool['.id'], [
                        'name' => $config['pool_name'],
                        'ranges' => $config['pool_range'],
                    ]);
                    $routerDetails[] = 'Pool diupdate';
                } else {
                    $mikrotik->addIpPool([
                        'name' => $config['pool_name'],
                        'ranges' => $config['pool_range'],
                        'comment' => '[billing-isolir-pool] Isolir pool - auto managed',
                    ]);
                    $routerDetails[] = 'Pool dibuat';
                }

                // 2. PPP Profile — find by comment marker or create
                $existingProfile = $mikrotik->findByComment('/ppp/profile', '[billing-isolir]');
                $profileParams = [
                    'name' => $config['profile_name'],
                    'local-address' => $config['local_address'],
                    'remote-address' => $config['pool_name'],
                    'rate-limit' => $config['rate_limit'],
                ];
                if ($config['dns_server']) {
                    $profileParams['dns-server'] = $config['dns_server'];
                }

                if ($existingProfile) {
                    $mikrotik->updatePppProfile($existingProfile['.id'], $profileParams);
                    $routerDetails[] = 'Profile diupdate';
                } else {
                    $profileParams['comment'] = '[billing-isolir] Isolir profile - auto managed';
                    $mikrotik->addPppProfile($profileParams);
                    $routerDetails[] = 'Profile dibuat';
                }

                // 3. Firewall Filter — block all except DNS & HTTP from isolir subnet
                $existingFilter = $mikrotik->findByComment('/ip/firewall/filter', '[billing-isolir-block]');
                $filterParams = [
                    'chain' => 'forward',
                    'src-address' => $subnet,
                    'protocol' => 'tcp',
                    'dst-port' => '!53,80,443',
                    'action' => 'drop',
                ];

                if ($existingFilter) {
                    $mikrotik->updateFirewallFilter($existingFilter['.id'], $filterParams);
                    $routerDetails[] = 'Firewall filter diupdate';
                } else {
                    $filterParams['comment'] = '[billing-isolir-block] Block isolir traffic - auto managed';
                    $mikrotik->addFirewallFilter($filterParams);
                    $routerDetails[] = 'Firewall filter dibuat';
                }

                // 4. Firewall NAT — redirect HTTP to billing server
                if ($config['dns_server']) {
                    $existingNat = $mikrotik->findByComment('/ip/firewall/nat', '[billing-isolir-redirect]');
                    $natParams = [
                        'chain' => 'dstnat',
                        'src-address' => $subnet,
                        'protocol' => 'tcp',
                        'dst-port' => '80',
                        'action' => 'dst-nat',
                        'to-addresses' => $config['dns_server'],
                        'to-ports' => '80',
                    ];

                    if ($existingNat) {
                        $mikrotik->updateFirewallNat($existingNat['.id'], $natParams);
                        $routerDetails[] = 'NAT redirect diupdate';
                    } else {
                        $natParams['comment'] = '[billing-isolir-redirect] Redirect isolir to billing - auto managed';
                        $mikrotik->addFirewallNat($natParams);
                        $routerDetails[] = 'NAT redirect dibuat';
                    }
                }

                $details[] = ['router' => $router->name, 'success' => true, 'status' => implode(', ', $routerDetails)];
                $successCount++;

            } catch (\Exception $e) {
                $details[] = ['router' => $router->name, 'success' => false, 'status' => $e->getMessage()];
                $failCount++;
            }
        }

        $this->activityLog->log('pop_settings', "Sync isolir setup: {$successCount} berhasil, {$failCount} gagal dari " . count($routers) . " router");

        return response()->json([
            'success' => $failCount === 0,
            'message' => "Sync selesai: {$successCount} berhasil, {$failCount} gagal dari " . count($routers) . " router.",
            'details' => $details,
        ]);
    }

    /**
     * Preview invoice template
     */
    public function previewInvoice(Request $request)
    {
        $userId = $request->query('user_id');
        $popSetting = $this->getPopSetting($userId);

        // Create dummy invoice data for preview
        $dummyInvoice = (object) [
            'invoice_number' => ($popSetting->invoice_prefix ?? 'INV') . '-' . date('Ym') . '-0001',
            'invoice_date' => now(),
            'due_date' => now()->addDays($popSetting->invoice_due_days ?? 7),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'customer' => (object) [
                'customer_id' => 'CUST-001',
                'name' => 'John Doe (Contoh Pelanggan)',
                'phone' => '081234567890',
                'address' => 'Jl. Contoh Alamat No. 123, RT 01/RW 02, Kelurahan Contoh, Kecamatan Contoh, Kota Contoh 12345',
                'email' => 'johndoe@example.com',
            ],
            'items' => [
                ['description' => 'Layanan Internet - Paket Premium 50 Mbps', 'amount' => 350000],
                ['description' => 'Biaya Instalasi (Promo)', 'amount' => 0],
            ],
            'subtotal' => 350000,
            'discount_amount' => 0,
            'tax_amount' => $popSetting->ppn_enabled ? round(350000 * ($popSetting->ppn_percentage / 100)) : 0,
            'total_amount' => $popSetting->ppn_enabled 
                ? 350000 + round(350000 * ($popSetting->ppn_percentage / 100))
                : 350000,
            'paid_amount' => 0,
            'remaining_amount' => $popSetting->ppn_enabled 
                ? 350000 + round(350000 * ($popSetting->ppn_percentage / 100))
                : 350000,
            'notes' => $popSetting->invoice_notes ?? 'Catatan invoice akan muncul di sini.',
            'created_at' => now(),
        ];

        return view('admin.pop-settings.preview-invoice', [
            'invoice' => $dummyInvoice,
            'popSetting' => $popSetting,
            'isPreview' => true,
        ]);
    }

    /**
     * Live preview invoice template via AJAX
     */
    public function livePreviewInvoice(Request $request)
    {
        $userId = $request->input('user_id');
        $popSetting = $this->getPopSetting($userId);

        // Override with form data for live preview
        $popSetting->invoice_prefix = $request->input('invoice_prefix', $popSetting->invoice_prefix ?? 'INV');
        $popSetting->invoice_due_days = $request->input('invoice_due_days', $popSetting->invoice_due_days ?? 7);
        $popSetting->invoice_notes = $request->input('invoice_notes', $popSetting->invoice_notes);
        $popSetting->invoice_footer = $request->input('invoice_footer', $popSetting->invoice_footer);
        $popSetting->invoice_terms = $request->input('invoice_terms', $popSetting->invoice_terms);
        $popSetting->ppn_enabled = $request->boolean('ppn_enabled');
        $popSetting->ppn_percentage = $request->input('ppn_percentage', $popSetting->ppn_percentage ?? 11);
        
        // Parse bank accounts
        $bankAccounts = $request->input('bank_accounts', []);
        if (is_array($bankAccounts)) {
            $popSetting->bank_accounts = collect($bankAccounts)->filter(function ($item) {
                return !empty($item['bank_name']) || !empty($item['account_number']);
            })->values()->toArray();
        }

        // Create dummy invoice data
        $subtotal = 350000;
        $taxAmount = $popSetting->ppn_enabled ? round($subtotal * ($popSetting->ppn_percentage / 100)) : 0;
        
        $dummyInvoice = (object) [
            'invoice_number' => $popSetting->invoice_prefix . '-' . date('Ym') . '-0001',
            'invoice_date' => now(),
            'due_date' => now()->addDays($popSetting->invoice_due_days ?? 7),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'customer' => (object) [
                'customer_id' => 'CUST-001',
                'name' => 'John Doe (Contoh Pelanggan)',
                'phone' => '081234567890',
                'address' => 'Jl. Contoh Alamat No. 123, RT 01/RW 02, Kelurahan Contoh, Kecamatan Contoh, Kota Contoh 12345',
                'email' => 'johndoe@example.com',
            ],
            'items' => [
                ['description' => 'Layanan Internet - Paket Premium 50 Mbps', 'amount' => 350000],
            ],
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
            'paid_amount' => 0,
            'remaining_amount' => $subtotal + $taxAmount,
            'notes' => $popSetting->invoice_notes ?? '',
            'created_at' => now(),
        ];

        return view('admin.pop-settings.partials.invoice-preview-content', [
            'invoice' => $dummyInvoice,
            'popSetting' => $popSetting,
            'isPreview' => true,
        ]);
    }
}
