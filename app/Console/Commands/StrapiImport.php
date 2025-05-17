<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StrapiImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strapi:import {album} {user_id} {base_url=https://bg-strapi-h3d4k.ondigitalocean.app}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import album data from Strapi CMS';

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
        $baseUrl = $this->argument('base_url');
        $album = $this->argument('album');
        $userId = $this->argument('user_id');
        
        // Add debug information
        $this->info("Import parameters:");
        $this->info("- Base URL: {$baseUrl}");
        $this->info("- Album: {$album}");
        $this->info("- User ID: {$userId}");
        $this->info("----------------------------");
        
        // Check if user ID is UUID format; if not, try to find by email or name
        $this->info("Attempting to find user with ID: {$userId}");
        $user = null;
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId)) {
            $this->info("Looking up user by UUID");
        $user = User::find($userId);
        } else {
            // Try to find by email if it looks like an email
            if (filter_var($userId, FILTER_VALIDATE_EMAIL)) {
                $this->info("Looking up user by email");
                $user = User::where('email', $userId)->first();
            } else {
                // Otherwise look for first user (for development/testing only)
                $this->warn("User ID is not a UUID or email. Attempting to get the first user.");
                $user = User::first();
                
                if ($user) {
                    $this->info("Using first user in database: {$user->name} (ID: {$user->id})");
                }
            }
        }
        
        if (!$user) {
            $this->error("User not found with identifier: {$userId}");
            $this->info("Available users:");
            $users = User::all(['id', 'name', 'email']);
            foreach ($users as $availableUser) {
                $this->info("ID: {$availableUser->id}, Name: {$availableUser->name}, Email: {$availableUser->email}");
            }
            return 1;
        }
        
        // Get the album data from Strapi after user is found
        $url = "{$baseUrl}/{$album}";
        $this->info("Importing album '{$album}' from {$url} for user {$user->name}");
        
        try {
            $response = Http::get($url);
            
            if (!$response->successful()) {
                $this->error("Failed to get album data from {$url}. Status code: {$response->status()}");
                $this->info("Response: " . $response->body());
                return 1;
            }
            
            $data = $response->json();
            
            if (empty($data)) {
                $this->error("No data returned from {$url}");
                return 1;
            }
            
            $this->info("Received " . count($data) . " items from Strapi endpoint");
            
            // Sample of first item structure for debugging
            if (count($data) > 0) {
                $this->info("Sample data structure of first item:");
                $this->info(json_encode(array_slice($data, 0, 1), JSON_PRETTY_PRINT));
            }
            
            // Create the album if it doesn't exist
            $albumModel = Album::firstOrCreate(
                ['user_id' => $user->id, 'title' => $album],
            [
                    'description' => "Imported from {$url}",
                    'cover_image' => '',
                    'id' => (string) Str::uuid(),
            ]
        );
        
            $this->info("Processing album: {$album} (ID: {$albumModel->id}) for user: {$user->name}");
            
            // Create directory for album images if it doesn't exist
            $albumDirectory = "albums/{$albumModel->id}";
            
            // Counter for successfully imported media
            $mediaCount = 0;
            $firstImageObject = null;
        
            // Process each media item
        foreach ($data as $item) {
                try {
                    // Extract all links from the item recursively
                    $mediaLinks = $this->extractMediaLinks($item);
                    
                    if (!empty($mediaLinks)) {
                        foreach ($mediaLinks as $media) {
                            $this->processMediaItem($albumModel, $media, $albumDirectory, $mediaCount, $firstImageObject);
                            $mediaCount++;
                        }
                    } else {
                        $this->warn("No media links found for item: " . json_encode($item));
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing item: {$e->getMessage()}");
                }
            }
            
            // Set the album cover image if found
            if ($firstImageObject) {
                $albumModel->cover_image_path = $firstImageObject['url'];
                $albumModel->save();
                $this->info("Updated album cover image to: {$albumModel->cover_image_path}");
                $this->info("Verified saved cover path: {$albumModel->cover_image_path}");
            } else {
                $this->warn("No suitable cover image found for album");
            }
            
            $this->info("Imported {$mediaCount} media items into album '{$album}'");
            $this->info("Import completed successfully");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Extract all media links from an item recursively
     * 
     * @param array $item
     * @return array
     */
    protected function extractMediaLinks(array $item): array
    {
        $links = [];
        
        // Direct link fields (most common cases)
        $possibleLinkFields = ['link', 'url', 'path', 'VideoLink', 'Link'];
        
        foreach ($possibleLinkFields as $field) {
            if (isset($item[$field]) && !empty($item[$field]) && is_string($item[$field])) {
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
            if (isset($item['Image']['url']) && !empty($item['Image']['url'])) {
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
            if (isset($item['image']['url']) && !empty($item['image']['url'])) {
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
                // Log the media item for debugging
                Log::info("Processing Media item: " . json_encode($mediaItem));
                
                // Check directly for Link field in the media item
                if (isset($mediaItem['Link']) && !empty($mediaItem['Link'])) {
                    $url = $mediaItem['Link'];
                    if (filter_var($url, FILTER_VALIDATE_URL) || $this->imageService->isVideoLink($url)) {
                        $this->info("Found direct Link in Media item: {$url}");
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
        
        // Check if it's a video link
        if ($this->imageService->isVideoLink($imageUrl)) {
            $this->info("Found video link: {$imageUrl}");
            
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
            
            $this->info("Video ID: {$videoId}, Type: {$videoType}");
            
            // Try to get a thumbnail
            $thumbnailResult = null;
            
            if ($videoId && $videoType == 'youtube') {
                // First try high-res thumbnail
                $hdThumbnailUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
                $this->info("Trying HD thumbnail URL: {$hdThumbnailUrl}");
                
                try {
                    $thumbnailResult = $this->imageService->storeImage($hdThumbnailUrl, $albumDirectory, true);
                    $this->info("HD thumbnail stored successfully: " . json_encode($thumbnailResult));
                } catch (\Exception $e) {
                    $this->info("HD thumbnail not available, trying standard resolution...");
                    
                    // Try standard resolution thumbnail
                    try {
                        $sdThumbnailUrl = "https://img.youtube.com/vi/{$videoId}/0.jpg";
                        $this->info("Trying SD thumbnail URL: {$sdThumbnailUrl}");
                        $thumbnailResult = $this->imageService->storeImage($sdThumbnailUrl, $albumDirectory, true);
                        $this->info("SD thumbnail stored successfully: " . json_encode($thumbnailResult));
                    } catch (\Exception $e) {
                        $this->error("Failed to download any thumbnail: {$e->getMessage()}");
                    }
                }
            } elseif ($videoType == 'vimeo') {
                // Use the normal method for Vimeo
                try {
                    $thumbnailResult = $this->imageService->storeVideoThumbnail($imageUrl, $albumDirectory);
                } catch (\Exception $e) {
                    $this->error("Failed to get Vimeo thumbnail: {$e->getMessage()}");
                }
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
            
            $albumImage = $albumModel->images()->create([
                'path' => $imageUrl,
                'title' => $media['title'] ?? null,
                'caption' => $media['caption'] ?? null,
                'alt_text' => $media['alt_text'] ?? null,
                'properties' => json_encode($properties),
                'order' => $media['order'] ?? $mediaCount,
            ]);
            
            $this->info("Created video entry with ID: {$albumImage->id}");
            
            // If this is our first media and we still don't have a cover image,
            // use the video thumbnail as the album cover
            if (!$firstImageObject && $thumbnailResult) {
                $firstImageObject = $thumbnailResult;
                $this->info("Setting video thumbnail as potential cover image: {$thumbnailResult['url']}");
            }
        } else {
            // Download and store the image
            $this->info("Downloading image: {$imageUrl}");
            
            try {
                // Use WebP conversion
                $result = $this->imageService->storeImage($imageUrl, $albumDirectory, true, true);
                $localPath = $result['path'];
                $publicUrl = $result['url'];
                
                $this->info("Saved image to: {$localPath}");
                
                // Keep track of the first non-video image for cover
                if (!$firstImageObject) {
                    $firstImageObject = $result;
                    $this->info("Setting as potential cover image: {$publicUrl}");
                }
                
                // Create album image
                $properties = [];
                
                // Add WebP URL if available
                if (isset($result['webp_url'])) {
                    $properties['webp_url'] = $result['webp_url'];
                    $this->info("Added WebP version: {$result['webp_url']}");
                }
                
                // Add year if available
                if (isset($media['year'])) {
                    $properties['year'] = $media['year'];
                }
                
                $albumImage = $albumModel->images()->create([
                    'path' => $publicUrl,
                    'title' => $media['title'] ?? null,
                    'caption' => $media['caption'] ?? null,
                    'alt_text' => $media['alt_text'] ?? null,
                    'properties' => json_encode($properties),
                    'order' => $media['order'] ?? $mediaCount,
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to download image: {$e->getMessage()}");
            }
        }
    }
} 