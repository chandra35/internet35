<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSlider;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LandingSliderController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:landing.sliders.view', only: ['index', 'show']),
            new Middleware('permission:landing.sliders.create', only: ['create', 'store']),
            new Middleware('permission:landing.sliders.edit', only: ['edit', 'update']),
            new Middleware('permission:landing.sliders.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $sliders = LandingSlider::orderBy('order')->get();
        return view('admin.landing.sliders.index', compact('sliders'));
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.sliders._form', ['slider' => null])->render(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|url',
            'link_text' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('sliders', 'public');
            $imagePath = basename($imagePath);
        }

        $slider = LandingSlider::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'image' => $imagePath,
            'link' => $request->link,
            'link_text' => $request->link_text,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->activityLog->logCreate('landing.sliders', "Created slider: {$slider->title}");

        return response()->json([
            'success' => true,
            'message' => 'Slider berhasil ditambahkan!',
        ]);
    }

    public function edit(LandingSlider $slider)
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.sliders._form', compact('slider'))->render(),
        ]);
    }

    public function update(Request $request, LandingSlider $slider)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|url',
            'link_text' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $oldData = $slider->toArray();

        if ($request->hasFile('image')) {
            // Delete old image
            if ($slider->image) {
                Storage::disk('public')->delete('sliders/' . $slider->image);
            }
            $imagePath = $request->file('image')->store('sliders', 'public');
            $slider->image = basename($imagePath);
        }

        $slider->update([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'link' => $request->link,
            'link_text' => $request->link_text,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        $this->activityLog->logUpdate('landing.sliders', "Updated slider: {$slider->title}", $oldData, $slider->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Slider berhasil diupdate!',
        ]);
    }

    public function destroy(LandingSlider $slider)
    {
        $oldData = $slider->toArray();
        
        if ($slider->image) {
            Storage::disk('public')->delete('sliders/' . $slider->image);
        }

        $slider->delete();

        $this->activityLog->logDelete('landing.sliders', "Deleted slider: {$slider->title}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Slider berhasil dihapus!',
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:landing_sliders,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->orders as $item) {
            LandingSlider::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan slider berhasil diupdate!',
        ]);
    }
}
