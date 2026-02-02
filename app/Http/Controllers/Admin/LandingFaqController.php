<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingFaq;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LandingFaqController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:landing.faqs.view', only: ['index', 'show']),
            new Middleware('permission:landing.faqs.create', only: ['create', 'store']),
            new Middleware('permission:landing.faqs.edit', only: ['edit', 'update']),
            new Middleware('permission:landing.faqs.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $faqs = LandingFaq::orderBy('order')->get();
        return view('admin.landing.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.faqs._form', ['faq' => null])->render(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $faq = LandingFaq::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $this->activityLog->logCreate('landing.faqs', "Created FAQ: {$faq->question}");

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil ditambahkan!',
        ]);
    }

    public function edit(LandingFaq $faq)
    {
        return response()->json([
            'success' => true,
            'html' => view('admin.landing.faqs._form', compact('faq'))->render(),
        ]);
    }

    public function update(Request $request, LandingFaq $faq)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $oldData = $faq->toArray();

        $faq->update([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        $this->activityLog->logUpdate('landing.faqs', "Updated FAQ: {$faq->question}", $oldData, $faq->toArray());

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil diupdate!',
        ]);
    }

    public function destroy(LandingFaq $faq)
    {
        $oldData = $faq->toArray();
        $faq->delete();

        $this->activityLog->logDelete('landing.faqs', "Deleted FAQ: {$faq->question}", $oldData);

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dihapus!',
        ]);
    }
}
