<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

trait HasPhotos
{
    /**
     * Get photos array
     */
    public function getPhotosArray(): array
    {
        return $this->photos ?? [];
    }

    /**
     * Get first photo URL
     */
    public function getFirstPhotoUrl(): ?string
    {
        $photos = $this->getPhotosArray();
        if (empty($photos)) {
            return null;
        }
        return $this->getPhotoUrl($photos[0]);
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrl(string $filename): string
    {
        return asset('storage/' . $this->getPhotoPath() . '/' . $filename);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(string $filename): string
    {
        return asset('storage/' . $this->getPhotoPath() . '/thumbs/' . $filename);
    }

    /**
     * Get photo storage path based on model type
     */
    public function getPhotoPath(): string
    {
        $type = strtolower(class_basename($this));
        return "photos/{$type}s";
    }

    /**
     * Upload photos from request
     * 
     * @param array $files Array of UploadedFile
     * @return array Array of saved filenames
     */
    public function uploadPhotos(array $files): array
    {
        $uploaded = [];
        $path = $this->getPhotoPath();
        $thumbPath = $path . '/thumbs';

        // Ensure directories exist
        Storage::disk('public')->makeDirectory($path);
        Storage::disk('public')->makeDirectory($thumbPath);

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                // Generate unique filename
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                // Save original
                $file->storeAs($path, $filename, 'public');

                // Create thumbnail (300x300)
                $this->createThumbnail($file, $thumbPath, $filename);

                $uploaded[] = $filename;
            }
        }

        return $uploaded;
    }

    /**
     * Create thumbnail for image
     */
    protected function createThumbnail(UploadedFile $file, string $thumbPath, string $filename): void
    {
        try {
            // Check if Intervention Image is available
            if (class_exists('Intervention\Image\Facades\Image')) {
                $img = Image::make($file->getRealPath());
                $img->fit(300, 300, function ($constraint) {
                    $constraint->upsize();
                });
                $img->save(storage_path('app/public/' . $thumbPath . '/' . $filename), 80);
            } else {
                // Fallback: just copy the original as thumbnail
                $file->storeAs($thumbPath, $filename, 'public');
            }
        } catch (\Exception $e) {
            // Fallback: just copy the original as thumbnail
            $file->storeAs($thumbPath, $filename, 'public');
        }
    }

    /**
     * Add photos to existing photos array
     */
    public function addPhotos(array $files): void
    {
        $newPhotos = $this->uploadPhotos($files);
        $existingPhotos = $this->getPhotosArray();
        $this->photos = array_merge($existingPhotos, $newPhotos);
        $this->save();
    }

    /**
     * Remove a photo
     */
    public function removePhoto(string $filename): void
    {
        $path = $this->getPhotoPath();
        
        // Delete files
        Storage::disk('public')->delete($path . '/' . $filename);
        Storage::disk('public')->delete($path . '/thumbs/' . $filename);

        // Update photos array
        $photos = $this->getPhotosArray();
        $photos = array_values(array_filter($photos, fn($p) => $p !== $filename));
        $this->photos = $photos;
        $this->save();
    }

    /**
     * Delete all photos
     */
    public function deleteAllPhotos(): void
    {
        $path = $this->getPhotoPath();
        
        foreach ($this->getPhotosArray() as $filename) {
            Storage::disk('public')->delete($path . '/' . $filename);
            Storage::disk('public')->delete($path . '/thumbs/' . $filename);
        }

        $this->photos = [];
        $this->save();
    }
}
