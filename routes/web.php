<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\LandingSliderController;
use App\Http\Controllers\Admin\LandingServiceController;
use App\Http\Controllers\Admin\LandingPackageController;
use App\Http\Controllers\Admin\LandingTestimonialController;
use App\Http\Controllers\Admin\LandingFaqController;
use App\Http\Controllers\Admin\LandingContentController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\RouterController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PppProfileController;
use App\Http\Controllers\Admin\IpPoolController;
use App\Http\Controllers\Admin\PopSettingController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\OdcController;
use App\Http\Controllers\Admin\OdpController;
use App\Http\Controllers\Admin\NetworkMapController;
use App\Http\Controllers\Pelanggan\DashboardController as PelangganDashboardController;
use App\Http\Controllers\Pelanggan\ProfileController as PelangganProfileController;
use App\Http\Controllers\Pelanggan\PaymentController as PelangganPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing Page
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])->name('landing');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::get('users/data', [UserController::class, 'getData'])->name('users.data');
    Route::get('users/{user}/password', [UserController::class, 'getPassword'])->name('users.password');
    Route::resource('users', UserController::class);
    
    // Role Management
    Route::resource('roles', RoleController::class);
    
    // Permission Management
    Route::post('permissions/scan', [PermissionController::class, 'scan'])->name('permissions.scan');
    Route::put('permissions/{permission}/update-group', [PermissionController::class, 'updateGroup'])->name('permissions.update-group');
    Route::resource('permissions', PermissionController::class);

    // Staff Management (for admin-pop to manage their team)
    Route::post('staff/{staff}/toggle-status', [\App\Http\Controllers\Admin\StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
    Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class);

    // Activity Logs
    Route::post('activity-logs/bulk-delete', [ActivityLogController::class, 'bulkDelete'])->name('activity-logs.bulk-delete');
    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show', 'destroy']);

    // Profile
    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    // Landing Page Management
    Route::prefix('landing')->name('landing.')->group(function () {
        // Sliders
        Route::post('sliders/reorder', [LandingSliderController::class, 'reorder'])->name('sliders.reorder');
        Route::resource('sliders', LandingSliderController::class);
        
        // Services
        Route::resource('services', LandingServiceController::class);
        
        // Packages
        Route::resource('packages', LandingPackageController::class);
        
        // Testimonials
        Route::resource('testimonials', LandingTestimonialController::class);
        
        // FAQs
        Route::resource('faqs', LandingFaqController::class);
        
        // Contents (hero, about, etc)
        Route::resource('contents', LandingContentController::class);
    });

    // Site Settings
    Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SiteSettingController::class, 'update'])->name('settings.update');

    // Router Management (Mikrotik)
    Route::post('routers/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');
    Route::get('routers/{router}/manage', [RouterController::class, 'manage'])->name('routers.manage');
    Route::get('routers/{router}/data', [RouterController::class, 'getData'])->name('routers.data');
    Route::post('routers/{router}/execute', [RouterController::class, 'executeCommand'])->name('routers.execute');
    Route::get('routers/{router}/refresh', [RouterController::class, 'refreshStatus'])->name('routers.refresh');
    Route::get('routers/{router}/ppp-secrets', [RouterController::class, 'getPPPSecrets'])->name('routers.ppp-secrets');
    Route::resource('routers', RouterController::class);

    // PPP Profile Management
    Route::get('ppp-profiles', [PppProfileController::class, 'index'])->name('ppp-profiles.index');
    Route::get('ppp-profiles/{profile}', [PppProfileController::class, 'show'])->name('ppp-profiles.show');
    Route::post('ppp-profiles/bulk-delete', [PppProfileController::class, 'bulkDelete'])->name('ppp-profiles.bulk-delete');
    Route::post('ppp-profiles/{router}/preview', [PppProfileController::class, 'preview'])->name('ppp-profiles.preview');
    Route::post('ppp-profiles/{router}/sync', [PppProfileController::class, 'sync'])->name('ppp-profiles.sync');
    Route::post('ppp-profiles/{router}/store', [PppProfileController::class, 'store'])->name('ppp-profiles.store');
    Route::put('ppp-profiles/{profile}', [PppProfileController::class, 'update'])->name('ppp-profiles.update');
    Route::delete('ppp-profiles/{profile}', [PppProfileController::class, 'destroy'])->name('ppp-profiles.destroy');

    // IP Pool Management
    Route::get('ip-pools', [IpPoolController::class, 'index'])->name('ip-pools.index');
    Route::get('ip-pools/{pool}', [IpPoolController::class, 'show'])->name('ip-pools.show');
    Route::get('ip-pools/{pool}/usage', [IpPoolController::class, 'getUsedIps'])->name('ip-pools.usage');
    Route::get('ip-pools/{router}/list', [IpPoolController::class, 'listForRouter'])->name('ip-pools.list');
    Route::post('ip-pools/bulk-delete', [IpPoolController::class, 'bulkDelete'])->name('ip-pools.bulk-delete');
    Route::post('ip-pools/{router}/preview', [IpPoolController::class, 'preview'])->name('ip-pools.preview');
    Route::post('ip-pools/{router}/sync', [IpPoolController::class, 'sync'])->name('ip-pools.sync');
    Route::post('ip-pools/{router}/store', [IpPoolController::class, 'store'])->name('ip-pools.store');
    Route::put('ip-pools/{pool}', [IpPoolController::class, 'update'])->name('ip-pools.update');
    Route::delete('ip-pools/{pool}', [IpPoolController::class, 'destroy'])->name('ip-pools.destroy');

    // Package Management (linked to PPP Profiles)
    Route::get('packages/profiles/{router}', [PackageController::class, 'getProfilesForRouter'])->name('packages.profiles');
    Route::resource('packages', PackageController::class);

    // POP Settings
    Route::prefix('pop-settings')->name('pop-settings.')->group(function () {
        // Monitoring (SuperAdmin only) - harus di atas route lain
        Route::get('/monitoring', [PopSettingController::class, 'monitoring'])->name('monitoring');
        Route::get('/view/{user}', [PopSettingController::class, 'viewPopDetail'])->name('view-detail');
        
        // ISP Info
        Route::get('/', [PopSettingController::class, 'index'])->name('index');
        Route::get('/isp-info', [PopSettingController::class, 'ispInfo'])->name('isp-info');
        Route::post('/isp-info', [PopSettingController::class, 'updateIspInfo'])->name('update-isp-info');
        Route::post('/remove-logo', [PopSettingController::class, 'removeLogo'])->name('remove-logo');
        
        // Invoice Settings
        Route::get('/invoice-settings', [PopSettingController::class, 'invoiceSettings'])->name('invoice-settings');
        Route::post('/invoice-settings', [PopSettingController::class, 'updateInvoiceSettings'])->name('update-invoice-settings');
        
        // Copy Settings
        Route::get('/copy-settings', [PopSettingController::class, 'copySettingsForm'])->name('copy-settings');
        Route::post('/copy', [PopSettingController::class, 'copySettings'])->name('copy');
        Route::get('/preview/{user}', [PopSettingController::class, 'preview'])->name('preview');
        
        // Integration Settings (Mikrotik & Radius)
        Route::get('/integration', [PopSettingController::class, 'integration'])->name('integration');
        Route::post('/integration', [PopSettingController::class, 'updateIntegration'])->name('update-integration');
        Route::post('/test-radius', [PopSettingController::class, 'testRadiusConnection'])->name('test-radius');
        
        // Region cascade
        Route::get('/cities/{province}', [PopSettingController::class, 'getCities']);
        Route::get('/districts/{city}', [PopSettingController::class, 'getDistricts']);
        Route::get('/villages/{district}', [PopSettingController::class, 'getVillages']);
    });

    // Message Templates (Email & WhatsApp)
    Route::prefix('message-templates')->name('message-templates.')->group(function () {
        Route::get('/', [MessageTemplateController::class, 'index'])->name('index');
        Route::get('/{code}/edit', [MessageTemplateController::class, 'edit'])->name('edit');
        Route::put('/{code}', [MessageTemplateController::class, 'update'])->name('update');
        Route::post('/{code}/reset', [MessageTemplateController::class, 'resetToDefault'])->name('reset');
        Route::post('/preview', [MessageTemplateController::class, 'preview'])->name('preview');
        Route::post('/send-test', [MessageTemplateController::class, 'sendTest'])->name('send-test');
    });

    // Payment Gateway
    Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
        Route::get('/', [PaymentGatewayController::class, 'index'])->name('index');
        Route::post('/', [PaymentGatewayController::class, 'store'])->name('store');
        Route::get('/{gateway}/edit', [PaymentGatewayController::class, 'edit'])->name('edit');
        Route::put('/{gateway}', [PaymentGatewayController::class, 'update'])->name('update');
        Route::delete('/{gateway}', [PaymentGatewayController::class, 'destroy'])->name('destroy');
        Route::post('/{gateway}/toggle', [PaymentGatewayController::class, 'toggleActive'])->name('toggle');
        Route::post('/{gateway}/test', [PaymentGatewayController::class, 'testConnection'])->name('test');
        
        // Sandbox
        Route::get('/sandbox-requests', [PaymentGatewayController::class, 'pendingSandboxRequests'])->name('sandbox-requests');
        Route::post('/{gateway}/submit-sandbox', [PaymentGatewayController::class, 'submitSandboxRequest'])->name('submit-sandbox');
        Route::post('/{gateway}/review-sandbox', [PaymentGatewayController::class, 'reviewSandboxRequest'])->name('review-sandbox');
    });

    // Notification Settings
    Route::prefix('notification-settings')->name('notification-settings.')->group(function () {
        Route::get('/', [NotificationSettingController::class, 'index'])->name('index');
        Route::post('/email', [NotificationSettingController::class, 'updateEmail'])->name('update-email');
        Route::post('/whatsapp', [NotificationSettingController::class, 'updateWhatsapp'])->name('update-whatsapp');
        Route::post('/telegram', [NotificationSettingController::class, 'updateTelegram'])->name('update-telegram');
        Route::post('/events', [NotificationSettingController::class, 'updateEvents'])->name('update-events');
        Route::post('/templates', [NotificationSettingController::class, 'updateTemplates'])->name('update-templates');
        Route::post('/reset-templates', [NotificationSettingController::class, 'resetTemplates'])->name('reset-templates');
        
        // Test
        Route::post('/test-email', [NotificationSettingController::class, 'testEmail'])->name('test-email');
        Route::post('/test-whatsapp', [NotificationSettingController::class, 'testWhatsapp'])->name('test-whatsapp');
        Route::post('/test-telegram', [NotificationSettingController::class, 'testTelegram'])->name('test-telegram');
    });

    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/search', [CustomerController::class, 'search'])->name('search');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/{customer}/password', [CustomerController::class, 'getPassword'])->name('password');
        Route::post('/{customer}/status', [CustomerController::class, 'changeStatus'])->name('change-status');
        Route::get('/packages/{router}', [CustomerController::class, 'getPackagesByRouter'])->name('packages-by-router');
        Route::post('/check-username', [CustomerController::class, 'checkUsername'])->name('check-username');
    });

    // ODC (Optical Distribution Cabinet) Management
    Route::prefix('odcs')->name('odcs.')->group(function () {
        Route::get('/', [OdcController::class, 'index'])->name('index');
        Route::get('/create', [OdcController::class, 'create'])->name('create');
        Route::post('/', [OdcController::class, 'store'])->name('store');
        Route::get('/by-router', [OdcController::class, 'getByRouter'])->name('by-router');
        Route::get('/{odc}', [OdcController::class, 'show'])->name('show');
        Route::get('/{odc}/edit', [OdcController::class, 'edit'])->name('edit');
        Route::put('/{odc}', [OdcController::class, 'update'])->name('update');
        Route::delete('/{odc}', [OdcController::class, 'destroy'])->name('destroy');
    });

    // ODP (Optical Distribution Point) Management
    Route::prefix('odps')->name('odps.')->group(function () {
        Route::get('/', [OdpController::class, 'index'])->name('index');
        Route::get('/create', [OdpController::class, 'create'])->name('create');
        Route::post('/', [OdpController::class, 'store'])->name('store');
        Route::get('/by-odc', [OdpController::class, 'getByOdc'])->name('by-odc');
        Route::get('/generate-code', [OdpController::class, 'generateCode'])->name('generate-code');
        Route::get('/{odp}', [OdpController::class, 'show'])->name('show');
        Route::get('/{odp}/edit', [OdpController::class, 'edit'])->name('edit');
        Route::put('/{odp}', [OdpController::class, 'update'])->name('update');
        Route::delete('/{odp}', [OdpController::class, 'destroy'])->name('destroy');
    });

    // OLT (Optical Line Terminal) Management
    Route::prefix('olts')->name('olts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OltController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\OltController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\OltController::class, 'store'])->name('store');
        Route::post('/identify', [\App\Http\Controllers\Admin\OltController::class, 'identify'])->name('identify');
        Route::get('/{olt}', [\App\Http\Controllers\Admin\OltController::class, 'show'])->name('show');
        Route::get('/{olt}/edit', [\App\Http\Controllers\Admin\OltController::class, 'edit'])->name('edit');
        Route::put('/{olt}', [\App\Http\Controllers\Admin\OltController::class, 'update'])->name('update');
        Route::delete('/{olt}', [\App\Http\Controllers\Admin\OltController::class, 'destroy'])->name('destroy');
        Route::post('/{olt}/test-connection', [\App\Http\Controllers\Admin\OltController::class, 'testConnection'])->name('test-connection');
        Route::get('/{olt}/test-connection-stream', [\App\Http\Controllers\Admin\OltController::class, 'testConnectionStream'])->name('test-connection-stream');
        Route::post('/{olt}/sync', [\App\Http\Controllers\Admin\OltController::class, 'sync'])->name('sync');
        Route::get('/{olt}/sync-stream', [\App\Http\Controllers\Admin\OltController::class, 'syncStream'])->name('sync-stream');
        Route::get('/{olt}/unregistered-onus', [\App\Http\Controllers\Admin\OltController::class, 'getUnregisteredOnus'])->name('unregistered-onus');
        Route::get('/{olt}/signal-history', [\App\Http\Controllers\Admin\OltController::class, 'getSignalHistory'])->name('signal-history');
    });

    // ONU (Optical Network Unit) Management
    Route::prefix('onus')->name('onus.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OnuController::class, 'index'])->name('index');
        Route::get('/{onu}', [\App\Http\Controllers\Admin\OnuController::class, 'show'])->name('show');
        Route::get('/{onu}/signal-history', [\App\Http\Controllers\Admin\OnuController::class, 'signalHistory'])->name('signal-history');
        Route::post('/register', [\App\Http\Controllers\Admin\OnuController::class, 'register'])->name('register');
        Route::post('/{onu}/unregister', [\App\Http\Controllers\Admin\OnuController::class, 'unregister'])->name('unregister');
        Route::post('/{onu}/reboot', [\App\Http\Controllers\Admin\OnuController::class, 'reboot'])->name('reboot');
        Route::post('/{onu}/refresh', [\App\Http\Controllers\Admin\OnuController::class, 'refresh'])->name('refresh');
        Route::post('/{onu}/assign-customer', [\App\Http\Controllers\Admin\OnuController::class, 'assignCustomer'])->name('assign-customer');
    });

    // Network Map
    Route::prefix('network-map')->name('network-map.')->group(function () {
        Route::get('/', [NetworkMapController::class, 'index'])->name('index');
        Route::get('/data', [NetworkMapController::class, 'getData'])->name('data');
        Route::get('/stats', [NetworkMapController::class, 'getStats'])->name('stats');
    });
});

/*
|--------------------------------------------------------------------------
| Client Routes (for future)
|--------------------------------------------------------------------------
*/
Route::prefix('client')->middleware(['auth', 'role:client'])->name('client.')->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('pelanggan.dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Pelanggan Routes (Customer Portal)
|--------------------------------------------------------------------------
*/
Route::prefix('pelanggan')->middleware(['auth', 'role:client'])->name('pelanggan.')->group(function () {
    // Dashboard
    Route::get('/', [PelangganDashboardController::class, 'index'])->name('dashboard');
    Route::get('/connection', [PelangganDashboardController::class, 'connection'])->name('connection');
    Route::get('/credentials', [PelangganDashboardController::class, 'credentials'])->name('credentials');
    
    // Profile
    Route::get('/profile', [PelangganProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [PelangganProfileController::class, 'update'])->name('profile.update');
    Route::get('/password', [PelangganProfileController::class, 'password'])->name('password');
    Route::put('/password', [PelangganProfileController::class, 'updatePassword'])->name('password.update');
    Route::post('/profile/photo', [PelangganProfileController::class, 'updatePhoto'])->name('profile.photo');
    
    // Invoices
    Route::get('/invoices', [PelangganPaymentController::class, 'invoices'])->name('invoices');
    Route::get('/invoice/{invoice}', [PelangganPaymentController::class, 'showInvoice'])->name('invoice');
    Route::post('/invoice/{invoice}/pay', [PelangganPaymentController::class, 'pay'])->name('pay');
    
    // Payments
    Route::get('/payments', [PelangganPaymentController::class, 'history'])->name('payments');
    Route::get('/payment/{payment}/confirm', [PelangganPaymentController::class, 'confirm'])->name('payment.confirm');
    Route::post('/payment/{payment}/confirm', [PelangganPaymentController::class, 'confirmManual'])->name('payment.confirm-manual');
    Route::post('/payment/{payment}/cancel', [PelangganPaymentController::class, 'cancel'])->name('payment.cancel');
});


