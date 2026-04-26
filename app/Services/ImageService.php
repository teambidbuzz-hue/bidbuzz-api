<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    /**
     * Get the configured media storage disk name.
     *
     * @return string
     */
    public static function mediaDiskName(): string
    {
        return config('filesystems.media_disk', 'public');
    }

    /**
     * Get the configured media storage disk instance.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public static function getMediaDisk()
    {
        return Storage::disk(static::mediaDiskName());
    }

    /**
     * Generate a public URL for a stored media file.
     *
     * For the 'public' (local) disk, returns url('storage/' . $path).
     * For cloud disks (r2, s3), returns Storage::disk()->url($path).
     *
     * @param string|null $path The relative storage path
     * @return string|null
     */
    public static function mediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $disk = static::mediaDiskName();

        if ($disk === 'public') {
            return url('storage/' . $path);
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Process an image (resize, compress to WebP) and store it.
     *
     * @param UploadedFile $file The uploaded file instance
     * @param string $path The directory path to store the file
     * @return string The relative path to the stored file
     */
    public static function processAndStore(UploadedFile $file, string $path): string
    {
        $manager = new ImageManager(new Driver());
        
        $image = $manager->decode($file->getRealPath());

        // Scale down the image so that the largest side is at most 800px, preserving aspect ratio.
        $image->scaleDown(800, 800);

        // Encode to WebP format with 80% quality
        $encoded = $image->encode(new \Intervention\Image\Encoders\WebpEncoder(80));

        // Generate a unique filename
        $filename = uniqid() . '_' . time() . '.webp';
        $fullPath = trim($path, '/') . '/' . $filename;

        // Store to the configured media disk
        static::getMediaDisk()->put($fullPath, (string) $encoded);

        return $fullPath;
    }

    /**
     * Delete a file from the configured media disk.
     *
     * @param string|null $path The relative storage path
     * @return void
     */
    public static function deleteMedia(?string $path): void
    {
        if ($path) {
            static::getMediaDisk()->delete($path);
        }
    }
}
