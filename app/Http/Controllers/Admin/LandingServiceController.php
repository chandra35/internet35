<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LandingServiceController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:landing.services.view', only: ['index', 'show']),
            new Middleware('permission:landing.services.create', only: ['create', 'store']),
            new Middleware('permission:landing.services.edit', only: ['edit', 'update']),
            new Middleware('permission:landing.services.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $services = LandingService::orderBy('order')->get();
        return view('admin.landing.services.index', compact('services'));
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.services._form', ['service' => null])->render(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
            $imagePath = basename($imagePath);
        }

        $service = LandingService::create([
            'title' => $request->title,
            'description' => $request->description,
            'icon' => $request->icon,
            'image' => $imagePath,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->activityLog->logCreate('landing.services', "Created service: {$service->title}");

        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil ditambahkan!',
        ]);
    }

    public function edit(LandingService $service)
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.services._form', compact('service'))->render(),
        ]);
    }

    public function update(Request $request, LandingService $service)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $oldData = $service->toArray();

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete('services/' . $service->image);
            }
            $imagePath = $request->file('image')->store('services', 'public');
            $service->image = basename($imagePath);
        }

        $service->update([
            'title' => $request->title,
            'description' => $request->description,
            'icon' => $request->icon,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        $this->activityLog->logUpdate('landing.services', "Updated service: {$service->title}", $oldData, $service->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil diupdate!',
        ]);
    }

    public function destroy(LandingService $service)
    {
        $oldData = $service->toArray();
        
        if ($service->image) {
            Storage::disk('public')->delete('services/' . $service->image);
        }

        $service->delete();

        $this->activityLog->logDelete('landing.services', "Deleted service: {$service->title}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil dihapus!',
        ]);
    }
}
