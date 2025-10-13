<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StrapiImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strapi:import {albums*} {--user=} {--base-url=https://bg-strapi-h3d4k.ondigitalocean.app} {--chunk=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import multiple albums from Strapi CMS with chunking';

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
        $baseUrl = $this->option('base-url');
        $albums = $this->argument('albums');
        $userId = $this->option('user');
        $chunkSize = $this->option('chunk');

        $this->info('Import parameters:');
        $this->info("- Base URL: {$baseUrl}");
        $this->info('- Albums: '.implode(', ', $albums));
        $this->info("- User ID/Email: {$userId}");
        $this->info("- Chunk size: {$chunkSize}");
        $this->info('----------------------------');

        // Find user
        $user = $this->findUser($userId);
        if (! $user) {
            return 1;
        }

        // Process albums in chunks
        $chunks = array_chunk($albums, $chunkSize);
        foreach ($chunks as $index => $albumChunk) {
            $this->info("\nProcessing chunk ".($index + 1).' of '.count($chunks));

            foreach ($albumChunk as $albumName) {
                $this->info("\nImporting album: {$albumName}");

                try {
                    $this->processAlbum($baseUrl, $albumName, $user);
                } catch (\Exception $e) {
                    $this->error("Failed to import album {$albumName}: {$e->getMessage()}");

                    continue;
                }

                // Clear some memory
                gc_collect_cycles();
            }

            if ($index < count($chunks) - 1) {
                $this->info("\nWaiting 5 seconds before next chunk...");
                sleep(5);
            }
        }

        return 0;
    }

    protected function findUser($userId)
    {
        $this->info("Attempting to find user with ID/Email: {$userId}");

        if (filter_var($userId, FILTER_VALIDATE_EMAIL)) {
            $this->info('Looking up user by email');
            $user = User::where('email', $userId)->first();
        } else {
            $this->info('Looking up user by ID');
            $user = User::find($userId);
        }

        if (! $user) {
            $this->error('User not found');
            $this->info('Available users:');
            User::all(['id', 'name', 'email'])->each(function ($user) {
                $this->info("ID: {$user->id}, Name: {$user->name}, Email: {$user->email}");
            });

            return null;
        }

        $this->info("Found user: {$user->name} (ID: {$user->id})");

        return $user;
    }

    protected function processAlbum($baseUrl, $albumName, $user)
    {
        $url = "{$baseUrl}/{$albumName}";
        $this->info("Fetching from: {$url}");

        $response = Http::get($url);
        if (! $response->successful()) {
            throw new \Exception("Failed to get album data. Status: {$response->status()}");
        }

        $data = $response->json();
        if (empty($data)) {
            throw new \Exception('No data returned from API');
        }

        // Create or update album without activity logging
        // We'll use DB transaction to bypass model events temporarily
        $album = Album::withoutEvents(function () use ($user, $albumName, $url) {
            return Album::firstOrCreate(
                ['user_id' => $user->id, 'title' => $albumName],
                [
                    'description' => "Imported from {$url}",
                    'id' => (string) Str::uuid(),
                    'order' => 0,
                ]
            );
        });

        $this->info("Processing album: {$albumName} (ID: {$album->id})");
        $albumDirectory = "albums/{$album->id}";

        // Process media items
        $mediaCount = 0;
        $firstImageObject = null;

        foreach ($data as $item) {
            try {
                $mediaLinks = $this->extractMediaLinks($item);
                foreach ($mediaLinks as $media) {
                    $this->processMediaItem($album, $media, $albumDirectory, $mediaCount, $firstImageObject);
                    $mediaCount++;
                }
            } catch (\Exception $e) {
                $this->warn("Error processing item in {$albumName}: {$e->getMessage()}");

                continue;
            }
        }

        // Update album cover if needed (also without events)
        if ($firstImageObject && ! $album->cover_image_path) {
            Album::withoutEvents(function () use ($album, $firstImageObject) {
                $album->cover_image_path = $firstImageObject['url'];
                $album->save();
            });
        }

        $this->info("Imported {$mediaCount} items into '{$albumName}'");
    }

    /**
     * Extract all media links from an item recursively
     */
    protected function extractMediaLinks(array $item): array
    {
        $links = [];

        // Direct link fields (most common cases)
        $possibleLinkFields = ['link', 'url', 'path', 'VideoLink', 'Link'];

        foreach ($possibleLinkFields as $field) {
            if (isset($item[$field]) && ! empty($item[$field]) && is_string($item[$field])) {
                $url = $item[$field];

                // Check if it's a link we can use
                if (filter_var($url, FILTER_VALIDATE_URL) || $this->imageService->isVideoLink($url)) {
                    $links[] = [
                        'url' => $url,
                        'title' => $item['Name'] ?? $item['name'] ?? $item['ProjectName'] ?? $item['Title'] ?? $item['title'] ?? null,
                        'caption' => $item['Caption'] ?? $item['caption'] ?? null,
                        'order' => $item['Order'] ?? $item['order'] ?? null,
                        'year' => $item['Year'] ?? $item['year'] ?? null,
                    ];
                }
            }
        }

        // Handle nested Image objects - Strapi specific structure
        if (isset($item['Image']) && is_array($item['Image'])) {
            // Prefer the root URL for Strapi images
            if (isset($item['Image']['url']) && ! empty($item['Image']['url'])) {
                $links[] = [
                    'url' => $item['Image']['url'],
                    'title' => $item['Name'] ?? $item['name'] ?? null,
                    'caption' => $item['Caption'] ?? $item['caption'] ?? null,
                    'order' => $item['Order'] ?? $item['order'] ?? null,
                    'year' => $item['Year'] ?? $item['year'] ?? null,
                    'alt_text' => $item['Image']['alternativeText'] ?? null,
                ];
            }
            // Look for formats if no root URL
            elseif (isset($item['Image']['formats']) && is_array($item['Image']['formats'])) {
                // Try to get the largest format (original is preferred)
                if (isset($item['Image']['formats']['large']['url'])) {
                    $url = $item['Image']['formats']['large']['url'];
                } elseif (isset($item['Image']['formats']['medium']['url'])) {
                    $url = $item['Image']['formats']['medium']['url'];
                } elseif (isset($item['Image']['formats']['small']['url'])) {
                    $url = $item['Image']['formats']['small']['url'];
                } elseif (isset($item['Image']['formats']['thumbnail']['url'])) {
                    $url = $item['Image']['formats']['thumbnail']['url'];
                } else {
                    $url = null;
                }

                if ($url) {
                    $links[] = [
                        'url' => $url,
                        'title' => $item['Name'] ?? $item['name'] ?? null,
                        'caption' => $item['Caption'] ?? $item['caption'] ?? null,
                        'order' => $item['Order'] ?? $item['order'] ?? null,
                        'year' => $item['Year'] ?? $item['year'] ?? null,
                        'alt_text' => $item['Image']['alternativeText'] ?? null,
                    ];
                }
            }
        }

        // Also handle lowercase 'image'
        if (isset($item['image']) && is_array($item['image'])) {
            // Prefer the root URL for Strapi images
            if (isset($item['image']['url']) && ! empty($item['image']['url'])) {
                $links[] = [
                    'url' => $item['image']['url'],
                    'title' => $item['Name'] ?? $item['name'] ?? null,
                    'caption' => $item['Caption'] ?? $item['caption'] ?? null,
                    'order' => $item['Order'] ?? $item['order'] ?? null,
                    'year' => $item['Year'] ?? $item['year'] ?? null,
                    'alt_text' => $item['image']['alternativeText'] ?? null,
                ];
            }
            // Look for formats if no root URL
            elseif (isset($item['image']['formats']) && is_array($item['image']['formats'])) {
                // Try to get the largest format available
                if (isset($item['image']['formats']['large']['url'])) {
                    $url = $item['image']['formats']['large']['url'];
                } elseif (isset($item['image']['formats']['medium']['url'])) {
                    $url = $item['image']['formats']['medium']['url'];
                } elseif (isset($item['image']['formats']['small']['url'])) {
                    $url = $item['image']['formats']['small']['url'];
                } elseif (isset($item['image']['formats']['thumbnail']['url'])) {
                    $url = $item['image']['formats']['thumbnail']['url'];
                } else {
                    $url = null;
                }

                if ($url) {
                    $links[] = [
                        'url' => $url,
                        'title' => $item['Name'] ?? $item['name'] ?? null,
                        'caption' => $item['Caption'] ?? $item['caption'] ?? null,
                        'order' => $item['Order'] ?? $item['order'] ?? null,
                        'year' => $item['Year'] ?? $item['year'] ?? null,
                        'alt_text' => $item['image']['alternativeText'] ?? null,
                    ];
                }
            }
        }

        // Check for Media array
        if (isset($item['Media']) && is_array($item['Media'])) {
            foreach ($item['Media'] as $mediaItem) {
                // Check directly for Link field in the media item
                if (isset($mediaItem['Link']) && ! empty($mediaItem['Link'])) {
                    $url = $mediaItem['Link'];
                    if (filter_var($url, FILTER_VALIDATE_URL) || $this->imageService->isVideoLink($url)) {
                        $links[] = [
                            'url' => $url,
                            'title' => $mediaItem['Name'] ?? $mediaItem['name'] ?? $item['ProjectName'] ?? $item['Title'] ?? $item['title'] ?? null,
                            'caption' => $mediaItem['Caption'] ?? $mediaItem['caption'] ?? null,
                            'order' => $mediaItem['Order'] ?? $mediaItem['order'] ?? null,
                            'year' => $item['Year'] ?? $item['year'] ?? null,
                        ];

                        continue; // Skip further processing for this item
                    }
                }

                // Process the rest of the media item
                $mediaLinks = $this->extractMediaLinks($mediaItem);
                $links = array_merge($links, $mediaLinks);
            }
        }

        // Recursively process any nested arrays
        foreach ($item as $key => $value) {
            if (is_array($value) && $key !== 'Image' && $key !== 'image' && $key !== 'Media' && $key !== 'formats') {
                $nestedLinks = $this->extractMediaLinks($value);
                $links = array_merge($links, $nestedLinks);
            }
        }

        return $links;
    }

    /**
     * Process a single media item
     */
    protected function processMediaItem($albumModel, $media, $albumDirectory, &$mediaCount, &$firstImageObject)
    {
        $imageUrl = $media['url'];

        // Determine the order - use provided order or fallback to media count
        $order = $media['order'] ?? $mediaCount;

        // Check if it's a video link
        if ($this->imageService->isVideoLink($imageUrl)) {
            $this->info("Processing video: {$imageUrl}");

            // Get video ID for YouTube/Vimeo
            $videoId = null;
            $videoType = null;

            if (strpos($imageUrl, 'youtube.com') !== false) {
                parse_str(parse_url($imageUrl, PHP_URL_QUERY), $params);
                $videoId = $params['v'] ?? null;
                $videoType = 'youtube';
            } elseif (strpos($imageUrl, 'youtu.be') !== false) {
                $path = parse_url($imageUrl, PHP_URL_PATH);
                $videoId = ltrim($path, '/');
                $videoType = 'youtube';
            } elseif (strpos($imageUrl, 'vimeo.com') !== false) {
                $path = parse_url($imageUrl, PHP_URL_PATH);
                $videoId = ltrim($path, '/');
                $videoType = 'vimeo';
            }

            // Try to get a thumbnail
            $thumbnailResult = null;

            if ($videoId && $videoType == 'youtube') {
                // First try high-res thumbnail
                $hdThumbnailUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";

                try {
                    $thumbnailResult = $this->imageService->storeImage($hdThumbnailUrl, $albumDirectory, true);
                } catch (\Exception $e) {
                    // Try standard resolution thumbnail
                    try {
                        $sdThumbnailUrl = "https://img.youtube.com/vi/{$videoId}/0.jpg";
                        $thumbnailResult = $this->imageService->storeImage($sdThumbnailUrl, $albumDirectory, true);
                    } catch (\Exception $e) {
                        $this->warn("Failed to download thumbnail for video: {$videoId}");
                        // Use a default video placeholder thumbnail
                        $thumbnailResult = ['url' => '/images/video-placeholder.svg'];
                    }
                }
            } elseif ($videoType == 'vimeo') {
                try {
                    $thumbnailResult = $this->imageService->storeVideoThumbnail($imageUrl, $albumDirectory);
                } catch (\Exception $e) {
                    $this->warn("Failed to get Vimeo thumbnail: {$e->getMessage()}");
                    // Use a default video placeholder thumbnail
                    $thumbnailResult = ['url' => '/images/video-placeholder.svg'];
                }
            } else {
                // For other video types or when video ID extraction fails
                $thumbnailResult = ['url' => '/images/video-placeholder.svg'];
            }

            // Store the video info even if thumbnail download fails
            $properties = [
                'type' => 'video',
                'video_id' => $videoId,
                'video_type' => $videoType,
                'year' => $media['year'] ?? null,
            ];

            if ($thumbnailResult) {
                $properties['thumbnail_url'] = $thumbnailResult['url'];
            }

            $albumImage = AlbumImage::withoutEvents(function () use ($albumModel, $imageUrl, $media, $order, $properties) {
                return $albumModel->images()->create([
                    'id' => (string) Str::uuid(),
                    'path' => $imageUrl,
                    'title' => $media['title'] ?? null,
                    'caption' => $media['caption'] ?? null,
                    'alt_text' => $media['alt_text'] ?? null,
                    'author' => null,
                    'date_created' => null,
                    'location' => null,
                    'tags' => null,
                    'properties' => json_encode($properties),
                    'order' => $order,
                ]);
            });

            // If this is our first media and we still don't have a cover image,
            // use the video thumbnail as the album cover
            if (! $firstImageObject && $thumbnailResult) {
                $firstImageObject = $thumbnailResult;
            }
        } else {
            // Download and store the image
            try {
                // Use WebP conversion
                $result = $this->imageService->storeImage($imageUrl, $albumDirectory, true, true);
                $publicUrl = $result['url'];

                // Keep track of the first non-video image for cover
                if (! $firstImageObject) {
                    $firstImageObject = $result;
                }

                // Create album image
                $properties = [];

                // Add WebP URL if available
                if (isset($result['webp_url'])) {
                    $properties['webp_url'] = $result['webp_url'];
                }

                // Add year if available
                if (isset($media['year'])) {
                    $properties['year'] = $media['year'];
                }

                $albumImage = AlbumImage::withoutEvents(function () use ($albumModel, $publicUrl, $media, $order, $properties) {
                    return $albumModel->images()->create([
                        'id' => (string) Str::uuid(),
                        'path' => $publicUrl,
                        'title' => $media['title'] ?? null,
                        'caption' => $media['caption'] ?? null,
                        'alt_text' => $media['alt_text'] ?? null,
                        'author' => null,
                        'date_created' => null,
                        'location' => null,
                        'tags' => null,
                        'properties' => json_encode($properties),
                        'order' => $order,
                    ]);
                });

                $title = $media['title'] ?? 'Untitled';
                $this->info("Processed: {$title} (order: {$order})");
            } catch (\Exception $e) {
                $this->error("Failed to download image {$imageUrl}: {$e->getMessage()}");
            }
        }
    }
}
