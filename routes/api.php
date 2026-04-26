<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\GenieAcsProvisionController;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Payment Gateway Webhooks (no CSRF, no auth)
Route::prefix('webhook')->group(function () {
    Route::post('/tripay', [WebhookController::class, 'tripay']);
    Route::post('/midtrans', [WebhookController::class, 'midtrans']);
    Route::post('/xendit', [WebhookController::class, 'xendit']);
    Route::post('/duitku', [WebhookController::class, 'duitku']);
    Route::post('/ipaymu', [WebhookController::class, 'ipaymu']);
});

// Alias: callback routes (some gateways use /callback/ instead of /webhook/)
Route::prefix('callback')->group(function () {
    Route::post('/tripay', [WebhookController::class, 'tripay']);
    Route::post('/midtrans', [WebhookController::class, 'midtrans']);
    Route::post('/xendit', [WebhookController::class, 'xendit']);
    Route::post('/duitku', [WebhookController::class, 'duitku']);
    Route::post('/ipaymu', [WebhookController::class, 'ipaymu']);
});

// GenieACS auto-provisioning endpoint (no CSRF, no session auth — key-based)
// Called by GenieACS Node.js extension on 0 BOOTSTRAP event.
Route::get('/acs/provision/{serial}', [GenieAcsProvisionController::class, 'getProvision'])
    ->name('api.acs.provision')
    ->where('serial', '[A-Za-z0-9%+._-]+');

// Wilayah Indonesia API
Route::prefix('wilayah')->group(function () {
    Route::get('/cities/{provinceCode}', function ($provinceCode) {
        return City::where('province_code', $provinceCode)->orderBy('name')->get(['code', 'name']);
    });
    
    Route::get('/districts/{cityCode}', function ($cityCode) {
        return District::where('city_code', $cityCode)->orderBy('name')->get(['code', 'name']);
    });
    
    Route::get('/villages/{districtCode}', function ($districtCode) {
        return Village::where('district_code', $districtCode)->orderBy('name')->get(['code', 'name']);
    });
});

// Routers API (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/routers/{router}/packages', function ($routerId) {
        return \App\Models\Package::where('router_id', $routerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'speed_name']);
    });
});

// Alternative without auth for internal use
Route::middleware('web')->group(function () {
    Route::get('/routers/{router}/packages', function ($routerId) {
        return \App\Models\Package::where('router_id', $routerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'speed_name']);
    });
});
