<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FirmwareFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FirmwareFileController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['bin', 'img', 'tar', 'gz', 'zip', 'ubi', 'trx', 'fw'];
    private const MAX_SIZE_MB = 64;

    // -----------------------------------------------------------------
    // Index — management page
    // -----------------------------------------------------------------

    public function index()
    {
        $firmwares = FirmwareFile::with('uploader')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('brand');

        return view('admin.firmware.index', compact('firmwares'));
    }

    // -----------------------------------------------------------------
    // Store — upload new firmware file
    // -----------------------------------------------------------------

    public function store(Request $request)
    {
        $request->validate([
            'brand'         => 'required|string|max:20',
            'model_pattern' => 'nullable|string|max:100',
            'version'       => 'required|string|max:100',
            'file'          => 'required|file|max:' . (self::MAX_SIZE_MB * 1024),
            'notes'         => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            return back()->withErrors(['file' => 'Format file tidak didukung. Gunakan: ' . implode(', ', self::ALLOWED_EXTENSIONS)]);
        }

        // Simpan dengan nama unik — brand_model_version_uuid.ext
        $brand   = Str::slug($request->brand, '_');
        $model   = $request->model_pattern ? Str::slug(str_replace('*', 'x', $request->model_pattern), '_') : 'all';
        $version = Str::slug($request->version, '_');
        $uuid    = Str::uuid()->toString();
        $filename = "{$brand}_{$model}_{$version}_{$uuid}.{$ext}";

        $file->storeAs('firmware', $filename, 'local');

        FirmwareFile::create([
            'brand'         => strtolower($request->brand),
            'model_pattern' => $request->model_pattern ?: null,
            'version'       => $request->version,
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'notes'         => $request->notes,
            'uploaded_by'   => (int) auth()->id(),
        ]);

        return back()->with('success', 'Firmware berhasil diupload.');
    }

    // -----------------------------------------------------------------
    // Destroy — delete firmware file
    // -----------------------------------------------------------------

    public function destroy(FirmwareFile $firmware)
    {
        $firmware->delete(); // model listener hapus file fisik
        return back()->with('success', 'Firmware berhasil dihapus.');
    }

    // -----------------------------------------------------------------
    // Download — serve file to ONU or admin
    // -----------------------------------------------------------------

    public function download(FirmwareFile $firmware)
    {
        $path = $firmware->storagePath();

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File firmware tidak ditemukan.');
        }

        return response()->download(
            Storage::disk('local')->path($path),
            $firmware->original_name
        );
    }

    // -----------------------------------------------------------------
    // List API — return compatible firmware for an ONU (brand + model)
    // Used by show.blade.php firmware tab via AJAX
    // -----------------------------------------------------------------

    public function listForOnu(Request $request)
    {
        $brand = strtolower($request->input('brand', ''));
        $model = $request->input('model');

        if (!$brand) {
            return response()->json([]);
        }

        $files = FirmwareFile::forOnu($brand, $model)->map(fn(FirmwareFile $fw) => [
            'id'            => $fw->id,
            'version'       => $fw->version,
            'original_name' => $fw->original_name,
            'model_pattern' => $fw->model_pattern ?: 'semua model',
            'file_size'     => $fw->file_size_human,
            'notes'         => $fw->notes,
            'download_url'  => $fw->download_url,
            'uploaded_at'   => $fw->created_at->diffForHumans(),
        ]);

        return response()->json($files);
    }
}
