<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingTestimonial;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LandingTestimonialController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:landing.testimonials.view', only: ['index', 'show']),
            new Middleware('permission:landing.testimonials.create', only: ['create', 'store']),
            new Middleware('permission:landing.testimonials.edit', only: ['edit', 'update']),
            new Middleware('permission:landing.testimonials.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $testimonials = LandingTestimonial::orderBy('order')->get();
        return view('admin.landing.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.testimonials._form', ['testimonial' => null])->render(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024',
            'rating' => 'required|integer|min:1|max:5',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('testimonials', 'public');
            $imagePath = basename($imagePath);
        }

        $testimonial = LandingTestimonial::create([
            'name' => $request->name,
            'position' => $request->position,
            'company' => $request->company,
            'content' => $request->content,
            'image' => $imagePath,
            'rating' => $request->rating,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->activityLog->logCreate('landing.testimonials', "Created testimonial: {$testimonial->name}");

        return response()->json([
            'success' => true,
            'message' => 'Testimoni berhasil ditambahkan!',
        ]);
    }

    public function edit(LandingTestimonial $testimonial)
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.testimonials._form', compact('testimonial'))->render(),
        ]);
    }

    public function update(Request $request, LandingTestimonial $testimonial)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024',
            'rating' => 'required|integer|min:1|max:5',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $oldData = $testimonial->toArray();

        if ($request->hasFile('image')) {
            if ($testimonial->image) {
                Storage::disk('public')->delete('testimonials/' . $testimonial->image);
            }
            $imagePath = $request->file('image')->store('testimonials', 'public');
            $testimonial->image = basename($imagePath);
        }

        $testimonial->update([
            'name' => $request->name,
            'position' => $request->position,
            'company' => $request->company,
            'content' => $request->content,
            'rating' => $request->rating,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        $this->activityLog->logUpdate('landing.testimonials', "Updated testimonial: {$testimonial->name}", $oldData, $testimonial->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Testimoni berhasil diupdate!',
        ]);
    }

    public function destroy(LandingTestimonial $testimonial)
    {
        $oldData = $testimonial->toArray();
        
        if ($testimonial->image) {
            Storage::disk('public')->delete('testimonials/' . $testimonial->image);
        }

        $testimonial->delete();

        $this->activityLog->logDelete('landing.testimonials', "Deleted testimonial: {$testimonial->name}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'Testimoni berhasil dihapus!',
        ]);
    }
}
