<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\MosaicController;
use App\Http\Controllers\Api\MediaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum', 'throttle:60,1'])->get('/user', function (Request $request) {
    return $request->user();
});

// Protected routes that require Sanctum authentication
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    
    // Album CRUD operations
    Route::prefix('albums')->group(function () {
        Route::get('/', [AlbumController::class, 'index']);
        Route::post('/', [AlbumController::class, 'store']);
        Route::get('/{id}', [AlbumController::class, 'show']);
        Route::put('/{id}', [AlbumController::class, 'update']);
        Route::delete('/{id}', [AlbumController::class, 'destroy']);
        Route::get('/by-title/{title}', [AlbumController::class, 'showByTitle']);
        
        // Album Images operations
        Route::prefix('{albumId}/images')->group(function () {
            Route::post('/', [AlbumController::class, 'storeImage']);
            Route::put('/reorder', [AlbumController::class, 'reorderImages']);
            Route::post('/store-video', [AlbumController::class, 'storeVideo']);
            Route::get('/{imageId}', [AlbumController::class, 'showImage']);
            Route::put('/{imageId}', [AlbumController::class, 'updateImage']);
            Route::delete('/{imageId}', [AlbumController::class, 'destroyImage']);
        });
    });

    // Mosaic CRUD operations
    Route::prefix('mosaics')->group(function () {
        Route::get('/', [MosaicController::class, 'index']);
        Route::post('/', [MosaicController::class, 'store']);
        Route::get('/{id}', [MosaicController::class, 'show']);
        Route::put('/{id}', [MosaicController::class, 'update']);
        Route::delete('/{id}', [MosaicController::class, 'destroy']);
        Route::get('/by-title/{title}', [MosaicController::class, 'showByTitle']);
        
        // Mosaic Items operations
        Route::prefix('{mosaicId}/items')->group(function () {
            Route::post('/', [MosaicController::class, 'storeItem']);
            Route::put('/{itemId}', [MosaicController::class, 'updateItem']);
            Route::delete('/{itemId}', [MosaicController::class, 'destroyItem']);
            Route::post('/reorder', [MosaicController::class, 'reorderItems']);
        });
    });
    
    // User-specific routes
    Route::prefix('user')->group(function () {
        Route::get('/albums', [AlbumController::class, 'userAlbums']);
        Route::get('/mosaics', [MosaicController::class, 'userMosaics']);
    });
});

// Public routes for API key access (for frontend websites)
Route::middleware(['api.key', 'throttle:60,1'])->prefix('public')->group(function () {
    // Public album access with user display settings
    Route::prefix('albums')->group(function () {
        Route::get('/by-title/{title}', [AlbumController::class, 'showByTitleWithApiKey']);
        Route::get('/{id}', [AlbumController::class, 'showWithApiKey']);
    });

    // Public mosaic access with user display settings
    Route::prefix('mosaics')->group(function () {
        Route::get('/by-title/{title}', [MosaicController::class, 'showByTitleWithApiKey']);
        Route::get('/{id}', [MosaicController::class, 'showWithApiKey']);
    });
});

// Media upload route with specific rate limiting and file size checks
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->middleware('file.size:10240') // 10MB limit
        ->name('api.media.upload');
});
