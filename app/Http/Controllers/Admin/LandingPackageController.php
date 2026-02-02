<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPackage;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LandingPackageController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:landing.packages.view', only: ['index', 'show']),
            new Middleware('permission:landing.packages.create', only: ['create', 'store']),
            new Middleware('permission:landing.packages.edit', only: ['edit', 'update']),
            new Middleware('permission:landing.packages.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $packages = LandingPackage::orderBy('order')->get();
        return view('admin.landing.packages.index', compact('packages'));
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.packages._form', ['package' => null])->render(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'period' => 'nullable|string|max:50',
            'speed' => 'required|string|max:50',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'is_popular' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $package = LandingPackage::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'period' => $request->period ?? 'bulan',
            'speed' => $request->speed,
            'features' => $request->features ?? [],
            'is_popular' => $request->boolean('is_popular', false),
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->activityLog->logCreate('landing.packages', "Created package: {$package->name}");

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil ditambahkan!',
        ]);
    }

    public function edit(LandingPackage $package)
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.packages._form', compact('package'))->render(),
        ]);
    }

    public function update(Request $request, LandingPackage $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'period' => 'nullable|string|max:50',
            'speed' => 'required|string|max:50',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'is_popular' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $oldData = $package->toArray();

        $package->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'period' => $request->period ?? 'bulan',
            'speed' => $request->speed,
            'features' => $request->features ?? [],
            'is_popular' => $request->boolean('is_popular', false),
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        $this->activityLog->logUpdate('landing.packages', "Updated package: {$package->name}", $oldData, $package->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil diupdate!',
        ]);
    }

    public function destroy(LandingPackage $package)
    {
        $oldData = $package->toArray();
        $package->delete();

        $this->activityLog->logDelete('landing.packages', "Deleted package: {$package->name}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil dihapus!',
        ]);
    }
}
