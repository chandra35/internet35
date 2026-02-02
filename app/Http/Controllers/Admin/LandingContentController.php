<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use Illuminate\Http\Request;

class LandingContentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:landing.contents.view')->only(['index']);
        $this->middleware('permission:landing.contents.create')->only(['create', 'store']);
        $this->middleware('permission:landing.contents.edit')->only(['edit', 'update']);
        $this->middleware('permission:landing.contents.delete')->only(['destroy']);
    }

    public function index()
    {
        $contents = LandingContent::orderBy('section')->orderBy('order')->get();
        $groupedContents = $contents->groupBy('section');
        
        return view('admin.landing.contents.index', compact('contents', 'groupedContents'));
    }

    public function create()
    {
        $content = null;
        $sections = [
            'hero' => 'Hero Section',
            'about' => 'Tentang Kami',
            'services' => 'Layanan',
            'packages' => 'Paket',
            'testimonials' => 'Testimoni',
            'faq' => 'FAQ',
            'contact' => 'Kontak',
            'footer' => 'Footer',
        ];
        $html = view('admin.landing.contents._form', compact('content', 'sections'))->render();
        
        return response()->json(['html' => $html]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'section' => 'required|string|max:100',
            'key' => 'required|string|max:255|unique:landing_contents,key',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable',
            'icon' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:255',
            'link_text' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $data = $request->except('image');
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/contents', $filename);
            $data['image'] = $filename;
        }

        $data['created_by'] = auth()->id();
        LandingContent::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil ditambahkan'
        ]);
    }

    public function edit(LandingContent $content)
    {
        $sections = [
            'hero' => 'Hero Section',
            'about' => 'Tentang Kami',
            'services' => 'Layanan',
            'packages' => 'Paket',
            'testimonials' => 'Testimoni',
            'faq' => 'FAQ',
            'contact' => 'Kontak',
            'footer' => 'Footer',
        ];
        $html = view('admin.landing.contents._form', compact('content', 'sections'))->render();
        
        return response()->json(['html' => $html]);
    }

    public function update(Request $request, LandingContent $content)
    {
        $request->validate([
            'section' => 'required|string|max:100',
            'key' => 'required|string|max:255|unique:landing_contents,key,' . $content->id,
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable',
            'icon' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:255',
            'link_text' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $data = $request->except('image');
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($content->image) {
                \Storage::delete('public/contents/' . $content->image);
            }
            
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/contents', $filename);
            $data['image'] = $filename;
        }

        $data['updated_by'] = auth()->id();
        $content->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil diperbarui'
        ]);
    }

    public function destroy(LandingContent $content)
    {
        // Delete image if exists
        if ($content->image) {
            \Storage::delete('public/contents/' . $content->image);
        }
        
        $content->delete();

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil dihapus'
        ]);
    }
}
