<?php

use App\Http\Controllers\Api\AdePlannerStatsController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MosaicController;
use App\Http\Middleware\ValidateApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - For External Frontends Using API Keys
|--------------------------------------------------------------------------
|
| These routes are designed for external websites/applications that need
| to display your albums and mosaics using API key authentication.
| They are READ-ONLY by design for security, with one exception: the
| anonymous ADE Planner counters (AdePlannerStatsController).
|
*/

// Counters only (artist hits, event favorites) on the key owner's ADE entries. Its own
// limit: every search and star on hiren.ninja lands here, from a handful of server IPs.
Route::middleware([ValidateApiKey::class, 'throttle:600,1'])
    ->post('/ade-planner/stats', [AdePlannerStatsController::class, 'store']);

// API Key protected routes - READ-ONLY for external frontends
Route::middleware([ValidateApiKey::class, 'throttle:60,1'])->group(function (): void {

    // Test connection endpoint
    Route::get('/test', function (Request $request): JsonResponse {
        return response()->json([
            'message' => 'API key authentication successful',
            'user' => $request->user()->name,
            'timestamp' => now()->toISOString(),
        ]);
    });

    // READ-ONLY Album access with user display settings
    Route::prefix('albums')->group(function (): void {
        Route::get('/', [AlbumController::class, 'indexWithApiKey']);
        Route::get('/by-title/{title}', [AlbumController::class, 'showByTitleWithApiKey']);
        Route::get('/{id}', [AlbumController::class, 'showWithApiKey']);
    });

    // READ-ONLY Mosaic access with user display settings
    Route::prefix('mosaics')->group(function (): void {
        Route::get('/', [MosaicController::class, 'indexWithApiKey']);
        Route::get('/by-title/{title}', [MosaicController::class, 'showByTitleWithApiKey']);
        Route::get('/{id}', [MosaicController::class, 'showWithApiKey']);
    });

    // Entry access (I AMS, Recipes, presets, etc.)
    Route::prefix('entries')->group(function (): void {
        Route::get('/', [EntryController::class, 'indexWithApiKey']);
        Route::get('/by-type/{type}', [EntryController::class, 'indexByTypeWithApiKey']);
        Route::get('/{id}', [EntryController::class, 'showWithApiKey']);
        // Writes act as the key owner, limited to granted entry types.
        Route::post('/', [EntryController::class, 'storeWithApiKey']);
        Route::put('/{id}', [EntryController::class, 'updateWithApiKey']);
        Route::patch('/{id}', [EntryController::class, 'updateWithApiKey']);
        Route::delete('/{id}', [EntryController::class, 'destroyWithApiKey']);
    });

});

// Internal API endpoint for media operations (used by your own frontend)
// These require session auth since they're called by your Inertia frontend
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function (): void {
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->name('api.media.upload');
    Route::delete('/media', [MediaController::class, 'delete'])
        ->name('api.media.delete');
});
