<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlbumImage;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;

class FixVideoThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:fix-thumbnails {--force : Force regenerate all thumbnails} {--missing-only : Only fix videos without thumbnails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix and generate thumbnails for video entries in albums';

    /**
     * The image service instance.
     *
     * @var \App\Services\ImageService
     */
    protected $imageService;

    /**
     * Create a new command instance.
     *
     * @param \App\Services\ImageService $imageService
     * @return void
     */
    public function __construct(ImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting video thumbnail generation...");
        
        $force = $this->option('force');
        $missingOnly = $this->option('missing-only');
        
        if ($force) {
            $this->info("Force mode enabled - will regenerate ALL video thumbnails");
        } elseif ($missingOnly) {
            $this->info("Missing-only mode - will only generate thumbnails for videos without them");
        }
        
        // Find all video entries
        $query = AlbumImage::whereJsonContains('properties->type', 'video')
            ->orWhere('path', 'like', '%youtube.com%')
            ->orWhere('path', 'like', '%youtu.be%')
            ->orWhere('path', 'like', '%vimeo.com%');
            
        $videos = $query->get();
        
        $this->info("Found {$videos->count()} video entries to process");
        
        if ($videos->count() === 0) {
            $this->info("No videos found to process.");
            return 0;
        }
        
        $this->withProgressBar($videos, function ($video) use ($force, $missingOnly) {
            $this->processVideo($video, $force, $missingOnly);
        });
        
        $this->newLine();
        $this->info("Video thumbnail generation completed!");
        
        return 0;
    }
    
    /**
     * Process a single video entry
     *
     * @param AlbumImage $video
     * @param bool $force
     * @param bool $missingOnly
     */
    private function processVideo(AlbumImage $video, bool $force, bool $missingOnly)
    {
        try {
            // Parse the properties
            $properties = json_decode($video->properties, true) ?: [];
            
            // Skip if already has thumbnail and not in force mode
            if (!$force && !$missingOnly && isset($properties['thumbnail_url']) && !empty($properties['thumbnail_url'])) {
                return;
            }
            
            // Skip if missing-only mode and already has thumbnail
            if ($missingOnly && isset($properties['thumbnail_url']) && !empty($properties['thumbnail_url'])) {
                return;
            }
            
            // Set type to video if not already set
            $properties['type'] = 'video';
            
            // Extract video information
            $url = $video->path;
            $videoId = $this->extractVideoId($url);
            $videoType = $this->getVideoType($url);
            
            if ($videoId) {
                $properties['video_id'] = $videoId;
                $properties['video_type'] = $videoType;
                $properties['video_url'] = $url;
            }
            
            // Generate thumbnail
            $albumDirectory = "albums/{$video->album_id}";
            $thumbnailResult = $this->imageService->storeVideoThumbnail($url, $albumDirectory, true);
            
            if ($thumbnailResult) {
                $properties['thumbnail_url'] = $thumbnailResult['url'];
                
                // Mark if it's a generated placeholder
                if (isset($thumbnailResult['is_placeholder']) && $thumbnailResult['is_placeholder']) {
                    $properties['is_placeholder_thumbnail'] = true;
                }
                
                // Update the record
                $video->properties = json_encode($properties);
                $video->save();
                
                Log::info("Generated thumbnail for video: {$video->id}", [
                    'video_url' => $url,
                    'thumbnail_url' => $thumbnailResult['url'],
                    'is_placeholder' => $thumbnailResult['is_placeholder'] ?? false
                ]);
            } else {
                Log::warning("Failed to generate thumbnail for video: {$video->id}", [
                    'video_url' => $url
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error("Error processing video {$video->id}: {$e->getMessage()}", [
                'video_path' => $video->path,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Extract video ID from URL
     *
     * @param string $url
     * @return string|null
     */
    private function extractVideoId(string $url): ?string
    {
        // YouTube
        if (strpos($url, 'youtube.com') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            return $params['v'] ?? null;
        } elseif (strpos($url, 'youtu.be') !== false) {
            $path = parse_url($url, PHP_URL_PATH);
            return ltrim($path, '/');
        }
        
        // Vimeo
        if (strpos($url, 'vimeo.com') !== false) {
            $path = parse_url($url, PHP_URL_PATH);
            return ltrim($path, '/');
        }
        
        return null;
    }
    
    /**
     * Get video type from URL
     *
     * @param string $url
     * @return string|null
     */
    private function getVideoType(string $url): ?string
    {
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        }
        
        if (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        }
        
        return null;
    }
} 