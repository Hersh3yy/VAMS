<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumImage;
use App\Services\AlbumService;
use App\Services\ImageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AlbumImageController
{
    use AuthorizesRequests;

    /**
     * The image service instance.
     *
     * @var \App\Services\ImageService
     */
    protected $imageService;

    /**
     * The album service instance.
     *
     * @var \App\Services\AlbumService
     */
    protected $albumService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ImageService $imageService, AlbumService $albumService)
    {
        $this->imageService = $imageService;
        $this->albumService = $albumService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Album $album)
    {
        // Quick PHP settings check (verify .user.ini is working)
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxBytes = $this->convertToBytes($uploadMaxFilesize);
        $postMaxBytes = $this->convertToBytes($postMaxSize);
        $requiredBytes = 20 * 1024 * 1024; // 20MB required
        
        $settingsOK = ($uploadMaxBytes >= $requiredBytes && $postMaxBytes >= $requiredBytes);
        
        if (!$settingsOK) {
            Log::error('PHP limits too low: upload_max_filesize='.$uploadMaxFilesize.', post_max_size='.$postMaxSize.' (need 20M)');
        }

        try {
            $request->validate(
                [
                    'images' => 'required|array',
                    'images.*' => 'required|file|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:20480', // 20MB max, includes HEIC/HEIF
                ],
                [
                    'images.required' => 'Please select at least one image to upload.',
                    'images.array' => 'Images must be provided as an array.',
                    'images.*.required' => 'One or more image files are missing.',
                    'images.*.file' => 'The uploaded file failed to upload. This may be due to file size limits, network issues, or unsupported file type.',
                    'images.*.mimes' => 'The file must be one of: jpeg, png, jpg, gif, webp, heic, heif. Detected type: :attribute',
                    'images.*.image' => 'All files must be valid images (jpeg, png, jpg, gif, etc.).',
                    'images.*.max' => 'One or more images exceed the maximum file size of 20MB. Please compress or resize your images before uploading.',
                ]
            );

            $this->authorize('update', $album);

            // Shift all existing images down to make room at the top
            $fileCount = count($request->file('images'));
            $album->images()->increment('order', $fileCount);

            $uploadedImages = [];
            $fileNames = [];
            foreach ($request->file('images') as $index => $image) {
                $result = $this->imageService->storeImage($image, "albums/{$album->id}");
                $albumImage = $album->images()->create([
                    'id' => Str::uuid(),
                    'path' => $result['url'],
                    'order' => $index,
                ]);
                $uploadedImages[] = $albumImage;
                $fileNames[] = $image->getClientOriginalName().' ('.round($image->getSize() / 1024 / 1024, 2).' MB)';
            }

            Log::info('✅ Uploaded '.count($uploadedImages).' image(s): '.implode(', ', $fileNames));

            // Check if this is an AJAX request (for individual uploads)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'images' => $uploadedImages,
                ]);
            }

            // Return Inertia response with updated album data
            return back()->with([
                'message' => 'Images uploaded successfully',
                'images' => $uploadedImages,
            ]);
        } catch (ValidationException $e) {
            // Log detailed validation failure
            $errors = $e->errors();
            $fileErrors = [];

            // Extract detailed file errors
            foreach ($errors as $field => $messages) {
                if (str_starts_with($field, 'images.')) {
                    $index = str_replace('images.', '', $field);
                    if ($request->hasFile('images') && isset($request->file('images')[$index])) {
                        $file = $request->file('images')[$index];
                        $fileErrors[$field] = [
                            'messages' => $messages,
                            'file_info' => [
                                'original_name' => $file->getClientOriginalName(),
                                'mime_type' => $file->getMimeType(),
                                'size' => $file->getSize(),
                                'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                                'extension' => $file->getClientOriginalExtension(),
                                'is_valid' => $file->isValid(),
                                'error_code' => $file->getError(),
                                'error_message' => $file->getErrorMessage(),
                            ],
                        ];
                    }
                }
            }

            // Get first error message for quick logging
            $firstError = '';
            $firstFileName = '';
            foreach ($fileErrors as $field => $errorData) {
                if (empty($firstError)) {
                    $firstFileName = $errorData['file_info']['original_name'] ?? $field;
                    $firstError = implode('; ', $errorData['messages']);
                    break;
                }
            }

            Log::warning('❌ Upload failed: '.$firstFileName.' - '.$firstError);

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                // Get the first error message for a user-friendly response
                $errors = $e->errors();
                $firstError = '';
                foreach ($errors as $field => $messages) {
                    if (is_array($messages) && count($messages) > 0) {
                        $firstError = $messages[0];
                        break;
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => $firstError ?: 'Validation failed. Please check your files and try again.',
                    'errors' => $errors,
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Upload error: '.$e->getMessage());

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error uploading image: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Error uploading images: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AlbumImage $albumImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AlbumImage $albumImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Album $album, AlbumImage $image)
    {
        // Ensure the image belongs to the album and user owns the album
        if ($image->album_id !== $album->id) {
            abort(404);
        }

        $this->authorize('update', $album);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'altText' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'dateCreated' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:20480', // 20MB max, includes HEIC/HEIF
        ]);

        // The mutators in the model will handle mapping to the appropriate columns

        if ($request->hasFile('image')) {
            // Use ImageService to store the replacement image
            $result = $this->imageService->storeImage(
                $request->file('image'),
                "albums/{$image->album_id}"
            );

            $validated['path'] = $result['url'];
        }

        $image->update($validated);

        return back()->with('message', 'Image updated successfully');
    }

    /**
     * Convert PHP ini size format to bytes (handles K, M, G)
     */
    private function convertToBytes(string $size): int
    {
        $size = trim($size);
        if (empty($size)) {
            return 0;
        }

        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get human-readable upload error message from PHP error code
     */
    private function getUploadErrorMessage(?int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_OK => 'UPLOAD_ERR_OK - No error',
            UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE - File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE - File exceeds MAX_FILE_SIZE in form',
            UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL - File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE - No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR - Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE - Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION - PHP extension stopped the upload',
            default => "Unknown error code: {$errorCode}",
        };
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album, AlbumImage $image)
    {
        // Ensure the image belongs to the album and user owns the album
        if ($image->album_id !== $album->id) {
            abort(404);
        }

        $this->authorize('delete', $album);

        try {
            // Delete the image from storage if it's a local file
            if (! $this->isVideoLink($image->path) && strpos($image->path, '/storage/') !== false) {
                // Extract the path relative to the storage directory
                $path = str_replace('/storage/', '', parse_url($image->path, PHP_URL_PATH));
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            // Delete the image record from the database
            $image->delete();

            // Reload the album with its updated images
            $album = $this->albumService->getAlbum($album, false);

            return back()->with([
                'Album' => $album,
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete image: '.$e->getMessage());

            return back()->withErrors([
                'message' => 'Failed to delete item: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Check if a URL is a video link (YouTube, Vimeo, etc.)
     */
    private function isVideoLink(string $url): bool
    {
        return
            strpos($url, 'youtube.com') !== false ||
            strpos($url, 'youtu.be') !== false ||
            strpos($url, 'vimeo.com') !== false;
    }

    public function reorder(Request $request, ?Album $album = null)
    {
        // Support both nested and non-nested routes
        if (! $album && $request->has('album_id')) {
            $album = Album::findOrFail($request->album_id);
        }

        $request->validate([
            'from_index' => 'required|integer|min:0',
            'to_index' => 'required|integer|min:0',
        ]);

        $fromIndex = $request->from_index;
        $toIndex = $request->to_index;

        // Get the album ID from the route or request
        $albumId = $album ? $album->id : $request->album_id;

        // Get all images for this album ordered by current order
        $images = AlbumImage::where('album_id', $albumId)
            ->orderBy('order')
            ->get();

        if ($fromIndex >= $images->count() || $toIndex >= $images->count()) {
            return back()->withErrors(['message' => 'Invalid index provided']);
        }

        // Reorder the collection
        $item = $images->splice($fromIndex, 1)->first();
        $images->splice($toIndex, 0, [$item]);

        // Update the order for all affected images
        DB::transaction(function () use ($images) {
            foreach ($images as $index => $image) {
                $image->order = $index;
                $image->save();
            }
        });

        // Reload the album with its updated images
        $album = $this->albumService->getAlbum($albumId, false);

        return back()->with([
            'Album' => $album,
            'message' => 'Image order updated successfully',
        ]);
    }

    /**
     * Store a video URL as an album image
     */
    public function storeVideo(Request $request, Album $album)
    {
        Log::info('Incoming video request data:', $request->all());

        try {
            $request->validate([
                'url' => 'required|url',
                'title' => 'nullable|string|max:255',
                'caption' => 'nullable|string',
            ]);

            $this->authorize('update', $album);

            // Shift all existing images down to make room at the top
            $album->images()->increment('order', 1);

            // Store video thumbnail using ImageService
            $thumbnailResult = $this->imageService->storeVideoThumbnail(
                $request->url,
                "albums/{$album->id}"
            );

            // Create properties JSON with video metadata
            $properties = [
                'type' => 'video',
                'video_url' => $request->url,
            ];

            // Add thumbnail URL if available
            if ($thumbnailResult) {
                $properties['thumbnail_url'] = $thumbnailResult['url'];
            }

            // Create the album image entry
            $albumImage = $album->images()->create([
                'path' => $request->url,
                'title' => $request->title,
                'caption' => $request->caption,
                'properties' => json_encode($properties),
                'order' => 0,
            ]);

            // Reload the album with its updated images
            $album = $this->albumService->getAlbum($album, false);

            return back()->with([
                'Album' => $album,
                'message' => 'Video added successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in AlbumImageController@storeVideo:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'message' => 'Error adding video: '.$e->getMessage(),
            ]);
        }
    }
}
