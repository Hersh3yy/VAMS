<?php

namespace App\Console\Commands;

use App\Models\AlbumImage;
use App\Services\ImageService;
use Illuminate\Console\Command;

class FixVideoProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:fix {--force : Force updating all videos even if already fixed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix properties for video entries in the database';

    /**
     * The image service instance.
     *
     * @var \App\Services\ImageService
     */
    protected $imageService;

    /**
     * Create a new command instance.
     *
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
        $this->info('Fixing video properties...');
        $force = $this->option('force');

        if ($force) {
            $this->info('Force mode enabled - will update all videos');
        }

        // Find all video entries
        $videos = AlbumImage::where('path', 'like', '%youtube.com%')
            ->orWhere('path', 'like', '%youtu.be%')
            ->orWhere('path', 'like', '%vimeo.com%')
            ->get();

        $this->info("Found {$videos->count()} videos that need to be fixed");

        $count = 0;
        foreach ($videos as $video) {
            $this->info("Processing video: {$video->path}");

            // Parse the properties
            $properties = json_decode($video->properties, true) ?: [];

            // Skip if already has type=video and not in force mode
            if (! $force && isset($properties['type']) && $properties['type'] === 'video' && isset($properties['thumbnail_url'])) {
                $this->info('  Already has type=video and thumbnail, skipping');

                continue;
            }

            // Set type to video
            $properties['type'] = 'video';

            // Extract video ID and type
            $videoId = null;
            $videoType = null;
            $url = $video->path;

            if (strpos($url, 'youtube.com') !== false) {
                parse_str(parse_url($url, PHP_URL_QUERY), $params);
                $videoId = $params['v'] ?? null;
                $videoType = 'youtube';
            } elseif (strpos($url, 'youtu.be') !== false) {
                $path = parse_url($url, PHP_URL_PATH);
                $videoId = ltrim($path, '/');
                $videoType = 'youtube';
            } elseif (strpos($url, 'vimeo.com') !== false) {
                $path = parse_url($url, PHP_URL_PATH);
                $videoId = ltrim($path, '/');
                $videoType = 'vimeo';
            }

            $this->info("  Extracted video ID: {$videoId}, Type: {$videoType}");

            if ($videoId) {
                $properties['video_id'] = $videoId;
                $properties['video_type'] = $videoType;

                // Get thumbnail if not set
                if (! isset($properties['thumbnail_url']) || $force) {
                    $this->info('  Generating thumbnail');
                    $albumDirectory = "albums/{$video->album_id}";

                    if ($videoType == 'youtube') {
                        $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";

                        try {
                            $result = $this->imageService->storeImage($thumbnailUrl, $albumDirectory, true);
                            if ($result) {
                                $properties['thumbnail_url'] = $result['url'];
                                $this->info("  Generated YouTube thumbnail: {$properties['thumbnail_url']}");
                            }
                        } catch (\Exception $e) {
                            $this->error("  Failed to download YouTube thumbnail: {$e->getMessage()}");

                            // Try SD thumbnail as fallback
                            try {
                                $sdThumbnailUrl = "https://img.youtube.com/vi/{$videoId}/0.jpg";
                                $result = $this->imageService->storeImage($sdThumbnailUrl, $albumDirectory, true);
                                if ($result) {
                                    $properties['thumbnail_url'] = $result['url'];
                                    $this->info("  Generated fallback thumbnail: {$properties['thumbnail_url']}");
                                }
                            } catch (\Exception $e) {
                                $this->error("  Failed to download fallback thumbnail: {$e->getMessage()}");
                            }
                        }
                    } else {
                        // Use the original method for Vimeo
                        $result = $this->imageService->storeVideoThumbnail($video->path, $albumDirectory);

                        if ($result) {
                            $properties['thumbnail_url'] = $result['url'];
                            $this->info("  Generated thumbnail: {$properties['thumbnail_url']}");
                        }
                    }
                }
            }

            // Update the record
            $video->properties = json_encode($properties);
            $video->save();

            $this->info('  Updated properties: '.json_encode($properties));
            $count++;
        }

        $this->info("Fixed {$count} video entries");

        return 0;
    }
}
