<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SiteSettingController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings.view', only: ['index']),
            new Middleware('permission:settings.edit', only: ['update']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $settings = SiteSetting::orderBy('group')->orderBy('order')->get()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [];
        $settings = SiteSetting::all();

        foreach ($settings as $setting) {
            $key = str_replace('.', '_', $setting->key);
            
            if ($setting->type === 'image') {
                $rules[$key] = 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
            } elseif ($setting->type === 'email') {
                $rules[$key] = 'nullable|email';
            } elseif ($setting->type === 'url') {
                $rules[$key] = 'nullable|url';
            } elseif ($setting->type === 'number') {
                $rules[$key] = 'nullable|numeric';
            } else {
                $rules[$key] = 'nullable|string';
            }
        }

        $request->validate($rules);

        $oldSettings = $settings->pluck('value', 'key')->toArray();
        $updatedKeys = [];

        foreach ($settings as $setting) {
            $key = str_replace('.', '_', $setting->key);
            
            if ($setting->type === 'image') {
                if ($request->hasFile($key)) {
                    // Delete old image
                    if ($setting->value) {
                        Storage::disk('public')->delete('settings/' . $setting->value);
                    }
                    $path = $request->file($key)->store('settings', 'public');
                    $setting->value = basename($path);
                    $setting->save();
                    $updatedKeys[] = $setting->key;
                }
            } else {
                $newValue = $request->input($key);
                if ($setting->value !== $newValue) {
                    $setting->value = $newValue;
                    $setting->save();
                    $updatedKeys[] = $setting->key;
                }
            }
        }

        if (!empty($updatedKeys)) {
            $this->activityLog->logUpdate('settings', "Updated settings: " . implode(', ', $updatedKeys), $oldSettings);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil disimpan!',
        ]);
    }
}
