<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for storing images in cloud storage
 *
 * Note: Image transformations are handled by ImageProcessor service
 * This service focuses on storage operations only
 */
class ImageService
{
    /**
     * Store an image file in the cloud storage
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file  The file to store, either an UploadedFile or a URL/path
     * @param  string  $folder  The folder to store the file in
     * @param  bool  $isUrl  Whether the file is a URL to download
     * @param  bool  $convertToWebp  Whether to convert the image to WebP format
     * @return array Returns an array with keys 'path', 'url', and optionally 'webp_path' and 'webp_url'
     */
    public function storeImage($file, string $folder, bool $isUrl = false, bool $convertToWebp = false): array
    {
        if ($isUrl) {
            return $this->storeImageFromUrl($file, $folder, $convertToWebp);
        } else {
            return $this->storeUploadedFile($file, $folder, $convertToWebp);
        }
    }

    /**
     * Store an uploaded file in cloud storage
     */
    private function storeUploadedFile(UploadedFile $file, string $folder, bool $convertToWebp = false): array
    {
        // Store the file in DigitalOcean Spaces
        $path = $file->store($folder, 'spaces');

        // Generate the full URL
        $url = $this->getPublicUrl($path);

        $result = [
            'path' => $path,
            'url' => $url,
        ];

        // Convert to WebP if requested
        if ($convertToWebp) {
            $webpResult = $this->convertToWebp($file, $folder);
            if ($webpResult) {
                $result['webp_path'] = $webpResult['path'];
                $result['webp_url'] = $webpResult['url'];
            }
        }

        return $result;
    }

    /**
     * Store an image from a URL in cloud storage
     */
    private function storeImageFromUrl(string $url, string $folder, bool $convertToWebp = false): array
    {
        // Get file content
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new Exception("Failed to download image from URL: $url");
        }

        // Generate a unique filename
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = Str::uuid().'.'.$extension;
        $path = "$folder/$filename";

        // Store the file in DigitalOcean Spaces
        Storage::disk('spaces')->put($path, $response->body());

        // Generate the full URL
        $url = $this->getPublicUrl($path);

        $result = [
            'path' => $path,
            'url' => $url,
        ];

        // Convert to WebP if requested
        if ($convertToWebp && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
            try {
                // Save the image locally first
                $tempPath = storage_path('app/temp_'.$filename);
                file_put_contents($tempPath, $response->body());

                // Convert to WebP
                $webpResult = $this->convertToWebpFromPath($tempPath, $folder);

                // Clean up
                @unlink($tempPath);

                if ($webpResult) {
                    $result['webp_path'] = $webpResult['path'];
                    $result['webp_url'] = $webpResult['url'];
                }
            } catch (Exception $e) {
                Log::error("WebP conversion failed: {$e->getMessage()}");
                // Fail gracefully, original image is still available
            }
        }

        return $result;
    }

    /**
     * Convert an uploaded file to WebP format
     */
    private function convertToWebp(UploadedFile $file, string $folder): ?array
    {
        try {
            // Convert the image to WebP using GD
            $image = null;
            $mime = $file->getMimeType();

            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                $image = imagecreatefromjpeg($file->getPathname());
            } elseif ($mime === 'image/png') {
                $image = imagecreatefrompng($file->getPathname());
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            } elseif ($mime === 'image/gif') {
                $image = imagecreatefromgif($file->getPathname());
            } else {
                return null; // Unsupported format
            }

            if (! $image) {
                return null;
            }

            // Generate a WebP filename
            $webpFilename = Str::uuid().'.webp';
            $tempPath = storage_path('app/temp_'.$webpFilename);

            // Save as WebP
            imagewebp($image, $tempPath, 80);
            imagedestroy($image);

            // Upload to storage
            $webpPath = "$folder/$webpFilename";
            $webpContents = file_get_contents($tempPath);
            Storage::disk('spaces')->put($webpPath, $webpContents);

            // Clean up temp file
            @unlink($tempPath);

            // Return paths
            return [
                'path' => $webpPath,
                'url' => $this->getPublicUrl($webpPath),
            ];
        } catch (Exception $e) {
            Log::error("WebP conversion failed: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Convert an image file at path to WebP format
     *
     * @param  string  $path  Local file path
     * @param  string  $folder  Storage folder
     */
    private function convertToWebpFromPath(string $path, string $folder): ?array
    {
        try {
            // Convert the image to WebP using GD
            $image = null;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($extension === 'jpg' || $extension === 'jpeg') {
                $image = imagecreatefromjpeg($path);
            } elseif ($extension === 'png') {
                $image = imagecreatefrompng($path);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            } elseif ($extension === 'gif') {
                $image = imagecreatefromgif($path);
            } else {
                return null; // Unsupported format
            }

            if (! $image) {
                return null;
            }

            // Generate a WebP filename
            $webpFilename = Str::uuid().'.webp';
            $tempPath = storage_path('app/temp_'.$webpFilename);

            // Save as WebP
            imagewebp($image, $tempPath, 80);
            imagedestroy($image);

            // Upload to storage
            $webpPath = "$folder/$webpFilename";
            $webpContents = file_get_contents($tempPath);
            Storage::disk('spaces')->put($webpPath, $webpContents);

            // Clean up temp file
            @unlink($tempPath);

            // Return paths
            return [
                'path' => $webpPath,
                'url' => $this->getPublicUrl($webpPath),
            ];
        } catch (Exception $e) {
            Log::error("WebP conversion failed: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Generate a public URL for a stored file
     */
    public function getPublicUrl(string $path): string
    {
        $endpoint = rtrim(config('filesystems.disks.spaces.endpoint'), '/');
        $bucket = config('filesystems.disks.spaces.bucket');

        return "{$endpoint}/{$bucket}/{$path}";
    }

    /**
     * Store a video thumbnail from a video URL with enhanced fallback logic
     *
     * @param  bool  $useDefaultOnFailure  Whether to generate a default thumbnail on failure
     */
    public function storeVideoThumbnail(string $videoUrl, string $folder, bool $useDefaultOnFailure = true): ?array
    {
        $thumbnailUrl = $this->getVideoThumbnailUrl($videoUrl);

        if (! $thumbnailUrl) {
            if ($useDefaultOnFailure) {
                return $this->generateDefaultVideoThumbnail($folder, $videoUrl);
            }

            return null;
        }

        try {
            return $this->storeImageFromUrl($thumbnailUrl, $folder);
        } catch (Exception $e) {
            Log::error("Failed to store video thumbnail: {$e->getMessage()}");

            // Try fallback for YouTube
            if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                $fallbackThumbnail = $this->getYouTubeFallbackThumbnail($videoUrl);
                if ($fallbackThumbnail) {
                    try {
                        return $this->storeImageFromUrl($fallbackThumbnail, $folder);
                    } catch (Exception $fallbackException) {
                        Log::error("Fallback thumbnail also failed: {$fallbackException->getMessage()}");
                    }
                }
            }

            if ($useDefaultOnFailure) {
                return $this->generateDefaultVideoThumbnail($folder, $videoUrl);
            }

            return null;
        }
    }

    /**
     * Get the thumbnail URL for a video
     */
    public function getVideoThumbnailUrl(string $url): ?string
    {
        // YouTube
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            // Extract video ID
            $videoId = null;

            if (strpos($url, 'youtube.com') !== false) {
                parse_str(parse_url($url, PHP_URL_QUERY), $params);
                $videoId = $params['v'] ?? null;
            } elseif (strpos($url, 'youtu.be') !== false) {
                $path = parse_url($url, PHP_URL_PATH);
                $videoId = ltrim($path, '/');
            }

            if ($videoId) {
                // YouTube thumbnail URL
                return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
            }
        }

        // Vimeo
        if (strpos($url, 'vimeo.com') !== false) {
            // Extract video ID
            $path = parse_url($url, PHP_URL_PATH);
            $videoId = ltrim($path, '/');

            if ($videoId) {
                try {
                    // Get Vimeo thumbnail via API
                    $response = Http::get("https://vimeo.com/api/v2/video/{$videoId}.json");
                    if ($response->successful()) {
                        $data = $response->json();
                        if (! empty($data[0]['thumbnail_large'])) {
                            return $data[0]['thumbnail_large'];
                        }
                    }
                } catch (\Exception $e) {
                    // If API fails, we'll return null
                }
            }
        }

        return null;
    }

    /**
     * Check if a URL is a video link
     */
    public function isVideoLink(string $url): bool
    {
        return
            strpos($url, 'youtube.com') !== false ||
            strpos($url, 'youtu.be') !== false ||
            strpos($url, 'vimeo.com') !== false;
    }

    /**
     * Get fallback YouTube thumbnail URL (standard resolution)
     */
    private function getYouTubeFallbackThumbnail(string $url): ?string
    {
        $videoId = null;

        if (strpos($url, 'youtube.com') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            $videoId = $params['v'] ?? null;
        } elseif (strpos($url, 'youtu.be') !== false) {
            $path = parse_url($url, PHP_URL_PATH);
            $videoId = ltrim($path, '/');
        }

        if ($videoId) {
            return "https://img.youtube.com/vi/{$videoId}/0.jpg";
        }

        return null;
    }

    /**
     * Generate a default video thumbnail placeholder
     */
    private function generateDefaultVideoThumbnail(string $folder, string $videoUrl = ''): array
    {
        // Create a simple video thumbnail image using GD
        $width = 480;
        $height = 360;

        // Create image
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $backgroundColor = imagecolorallocate($image, 45, 45, 45); // Dark gray
        $textColor = imagecolorallocate($image, 255, 255, 255); // White
        $playButtonColor = imagecolorallocate($image, 255, 0, 0); // Red

        // Fill background
        imagefill($image, 0, 0, $backgroundColor);

        // Draw play button (triangle)
        $playButtonSize = 40;
        $centerX = $width / 2;
        $centerY = $height / 2;

        // Play button background circle
        imagefilledellipse($image, $centerX, $centerY, $playButtonSize * 2, $playButtonSize * 2, $playButtonColor);

        // Play button triangle
        $trianglePoints = [
            $centerX - 12, $centerY - 15,
            $centerX - 12, $centerY + 15,
            $centerX + 15, $centerY,
        ];
        imagefilledpolygon($image, $trianglePoints, 3, $textColor);

        // Add text
        $text = 'VIDEO';
        if (function_exists('imagettftext')) {
            // Use built-in font if TTF is not available
            imagestring($image, 3, ($width / 2) - 20, $centerY + 40, $text, $textColor);
        } else {
            imagestring($image, 3, ($width / 2) - 20, $centerY + 40, $text, $textColor);
        }

        // Determine platform for branding
        $platform = '';
        if (strpos($videoUrl, 'youtube') !== false) {
            $platform = 'YouTube';
        } elseif (strpos($videoUrl, 'vimeo') !== false) {
            $platform = 'Vimeo';
        }

        if ($platform) {
            imagestring($image, 2, 10, 10, $platform, $textColor);
        }

        // Save to temporary file
        $filename = 'video_thumbnail_'.Str::uuid().'.jpg';
        $tempPath = storage_path('app/temp_'.$filename);

        imagejpeg($image, $tempPath, 80);
        imagedestroy($image);

        // Upload to storage
        $storagePath = "$folder/$filename";
        $imageContents = file_get_contents($tempPath);
        Storage::disk('spaces')->put($storagePath, $imageContents);

        // Clean up temp file
        @unlink($tempPath);

        return [
            'path' => $storagePath,
            'url' => $this->getPublicUrl($storagePath),
            'is_placeholder' => true,
        ];
    }
}
