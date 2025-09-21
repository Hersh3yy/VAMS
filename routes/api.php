<?php

use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MosaicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - For External Frontends Using API Keys
|--------------------------------------------------------------------------
|
| These routes are designed for external websites/applications that need
| to display your albums and mosaics using API key authentication.
| They are READ-ONLY by design for security.
|
*/

// API Key protected routes - READ-ONLY for external frontends
Route::middleware([\App\Http\Middleware\ValidateApiKey::class, 'throttle:60,1'])->group(function () {

    // Test connection endpoint
    Route::get('/test', function (Request $request) {
        return response()->json([
            'message' => 'API key authentication successful',
            'user' => $request->user()->name,
            'timestamp' => now()->toISOString(),
        ]);
    });

    // READ-ONLY Album access with user display settings
    Route::prefix('albums')->group(function () {
        Route::get('/', [AlbumController::class, 'indexWithApiKey']);
        Route::get('/by-title/{title}', [AlbumController::class, 'showByTitleWithApiKey']);
        Route::get('/{id}', [AlbumController::class, 'showWithApiKey']);
    });

    // READ-ONLY Mosaic access with user display settings
    Route::prefix('mosaics')->group(function () {
        Route::get('/', [MosaicController::class, 'indexWithApiKey']);
        Route::get('/by-title/{title}', [MosaicController::class, 'showByTitleWithApiKey']);
        Route::get('/{id}', [MosaicController::class, 'showWithApiKey']);
    });

});

// Internal API endpoint for media operations (used by your own frontend)
// These require session auth since they're called by your Inertia frontend
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->name('api.media.upload');
    Route::delete('/media', [MediaController::class, 'delete'])
        ->name('api.media.delete');
});
