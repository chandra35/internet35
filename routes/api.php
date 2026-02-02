<?php

use Illuminate\Support\Facades\Route;
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
