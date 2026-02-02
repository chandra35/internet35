<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Laravolt\Indonesia\Models\Province;

class ProfileController extends Controller
{
    /**
     * Show profile page
     */
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        if (!$customer) {
            return redirect()->route('pelanggan.dashboard');
        }
        
        $customer->load(['province', 'city', 'district', 'village']);
        
        // Get provinces for dropdown
        $provinces = Province::orderBy('name')->get();
        
        return view('pelanggan.profile', compact('user', 'customer', 'provinces'));
    }

    /**
     * Update profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        $request->validate([
            'phone' => 'required|string|max:20',
            'phone_alt' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'province_code' => 'nullable|string|max:2',
            'city_code' => 'nullable|string|max:4',
            'district_code' => 'nullable|string|max:7',
            'village_code' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
        ]);
        
        // Update customer data
        if ($customer) {
            $customer->update([
                'phone' => $request->phone,
                'phone_alt' => $request->phone_alt,
                'address' => $request->address ?? $customer->address,
                'province_code' => $request->province_code ?? $customer->province_code,
                'city_code' => $request->city_code ?? $customer->city_code,
                'district_code' => $request->district_code ?? $customer->district_code,
                'village_code' => $request->village_code ?? $customer->village_code,
                'postal_code' => $request->postal_code ?? $customer->postal_code,
            ]);
        }
        
        // Update user phone
        $user->update([
            'phone' => $request->phone,
        ]);
        
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Show change password form
     */
    public function password()
    {
        return view('pelanggan.password');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        
        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }
        
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        
        return back()->with('success', 'Password berhasil diubah.');
    }

    /**
     * Update profile photo
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|string', // Base64 image
        ]);
        
        $user = Auth::user();
        $customer = $user->customerProfile;
        
        // Save base64 image
        $image = $request->photo;
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);
            
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                return response()->json(['error' => 'Format tidak didukung'], 422);
            }
            
            $image = base64_decode($image);
            
            // Delete old photo if exists
            if ($customer && $customer->photo_selfie) {
                Storage::delete('public/customers/selfie/' . $customer->photo_selfie);
            }
            
            $filename = 'selfie_' . time() . '.' . $type;
            Storage::put('public/customers/selfie/' . $filename, $image);
            
            if ($customer) {
                $customer->update(['photo_selfie' => $filename]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui',
                'photo_url' => $customer->photo_selfie_url,
            ]);
        }
        
        return response()->json(['error' => 'Format gambar tidak valid'], 422);
    }
}
