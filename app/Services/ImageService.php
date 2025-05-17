<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Store an image file in the cloud storage
     *
     * @param \Illuminate\Http\UploadedFile|string $file The file to store, either an UploadedFile or a URL/path
     * @param string $folder The folder to store the file in
     * @param bool $isUrl Whether the file is a URL to download
     * @param bool $convertToWebp Whether to convert the image to WebP format
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
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param bool $convertToWebp
     * @return array
     */
    private function storeUploadedFile(UploadedFile $file, string $folder, bool $convertToWebp = false): array
    {
        // Store the file in DigitalOcean Spaces
        $path = $file->store($folder, 'spaces');
        
        // Generate the full URL
        $url = $this->getPublicUrl($path);
        
        $result = [
            'path' => $path,
            'url' => $url
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
     *
     * @param string $url
     * @param string $folder
     * @param bool $convertToWebp
     * @return array
     */
    private function storeImageFromUrl(string $url, string $folder, bool $convertToWebp = false): array
    {
        // Get file content
        $response = Http::timeout(30)->get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to download image from URL: $url");
        }
        
        // Generate a unique filename
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = Str::uuid() . '.' . $extension;
        $path = "$folder/$filename";
        
        // Store the file in DigitalOcean Spaces
        Storage::disk('spaces')->put($path, $response->body());
        
        // Generate the full URL
        $url = $this->getPublicUrl($path);
        
        $result = [
            'path' => $path,
            'url' => $url
        ];
        
        // Convert to WebP if requested
        if ($convertToWebp && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
            try {
                // Save the image locally first
                $tempPath = storage_path('app/temp_' . $filename);
                file_put_contents($tempPath, $response->body());
                
                // Convert to WebP
                $webpResult = $this->convertToWebpFromPath($tempPath, $folder);
                
                // Clean up
                @unlink($tempPath);
                
                if ($webpResult) {
                    $result['webp_path'] = $webpResult['path'];
                    $result['webp_url'] = $webpResult['url'];
                }
            } catch (\Exception $e) {
                Log::error("WebP conversion failed: {$e->getMessage()}");
                // Fail gracefully, original image is still available
            }
        }
        
        return $result;
    }
    
    /**
     * Convert an uploaded file to WebP format
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @return array|null
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
            
            if (!$image) {
                return null;
            }
            
            // Generate a WebP filename
            $webpFilename = Str::uuid() . '.webp';
            $tempPath = storage_path('app/temp_' . $webpFilename);
            
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
                'url' => $this->getPublicUrl($webpPath)
            ];
        } catch (\Exception $e) {
            Log::error("WebP conversion failed: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Convert an image file at path to WebP format
     *
     * @param string $path Local file path
     * @param string $folder Storage folder
     * @return array|null
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
            
            if (!$image) {
                return null;
            }
            
            // Generate a WebP filename
            $webpFilename = Str::uuid() . '.webp';
            $tempPath = storage_path('app/temp_' . $webpFilename);
            
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
                'url' => $this->getPublicUrl($webpPath)
            ];
        } catch (\Exception $e) {
            Log::error("WebP conversion failed: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Generate a public URL for a stored file
     *
     * @param string $path
     * @return string
     */
    public function getPublicUrl(string $path): string
    {
        $endpoint = rtrim(config('filesystems.disks.spaces.endpoint'), '/');
        $bucket = config('filesystems.disks.spaces.bucket');
        
        return "{$endpoint}/{$bucket}/{$path}";
    }
    
    /**
     * Store a video thumbnail from a video URL
     *
     * @param string $videoUrl
     * @param string $folder
     * @return array|null
     */
    public function storeVideoThumbnail(string $videoUrl, string $folder): ?array
    {
        $thumbnailUrl = $this->getVideoThumbnailUrl($videoUrl);
        
        if (!$thumbnailUrl) {
            return null;
        }
        
        try {
            return $this->storeImageFromUrl($thumbnailUrl, $folder);
        } catch (\Exception $e) {
            Log::error("Failed to store video thumbnail: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Get the thumbnail URL for a video
     *
     * @param string $url
     * @return string|null
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
                        if (!empty($data[0]['thumbnail_large'])) {
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
     *
     * @param string $url
     * @return bool
     */
    public function isVideoLink(string $url): bool
    {
        return (
            strpos($url, 'youtube.com') !== false || 
            strpos($url, 'youtu.be') !== false || 
            strpos($url, 'vimeo.com') !== false
        );
    }
} 