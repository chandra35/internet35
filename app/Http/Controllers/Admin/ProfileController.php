<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;

class ProfileController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Show profile page
     */
    public function index()
    {
        $user = auth()->user();
        return view('admin.profile.index', compact('user'));
    }

    /**
     * Update profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $oldData = $user->only(['name', 'email', 'phone', 'address']);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        $this->activityLog->logUpdate('profile', 'Updated profile information', $oldData, $user->only(['name', 'email', 'phone', 'address']));

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diupdate!',
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai!',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->activityLog->log('update_password', 'profile', 'Changed password');

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah!',
        ]);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|string', // Base64 image
        ]);

        $user = auth()->user();
        
        // Delete old avatar
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        // Process base64 image
        $imageData = $request->avatar;
        
        // Remove data URL prefix
        if (strpos($imageData, ';base64,') !== false) {
            list(, $imageData) = explode(';base64,', $imageData);
        }
        
        $imageData = base64_decode($imageData);
        
        // Generate filename
        $filename = $user->id . '_' . time() . '.jpg';
        
        // Ensure directory exists
        Storage::disk('public')->makeDirectory('avatars');
        
        // Save image
        Storage::disk('public')->put('avatars/' . $filename, $imageData);

        // Update user
        $user->update(['avatar' => $filename]);

        $this->activityLog->log('update_avatar', 'profile', 'Changed avatar');

        return response()->json([
            'success' => true,
            'message' => 'Avatar berhasil diupload!',
            'avatar_url' => $user->fresh()->avatar_url,
        ]);
    }

    /**
     * Delete avatar
     */
    public function deleteAvatar()
    {
        $user = auth()->user();

        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
            $user->update(['avatar' => null]);

            $this->activityLog->log('delete_avatar', 'profile', 'Deleted avatar');
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar berhasil dihapus!',
            'avatar_url' => $user->fresh()->avatar_url,
        ]);
    }
}
