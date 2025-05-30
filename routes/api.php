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

// Protected routes that require both Sanctum and API key
Route::middleware(['auth:sanctum', 'api.key', 'throttle:60,1'])->group(function () {
    // Album routes
    Route::prefix('albums')->group(function () {
        Route::get('/', [AlbumController::class, 'index']);
        Route::get('/{id}', [AlbumController::class, 'show']);
        Route::get('/by-title/{title}', [AlbumController::class, 'showByTitle']);
        Route::get('/by-title/{title}/with-key', [AlbumController::class, 'showByTitleWithApiKey']);
    });

    // Mosaic routes
    Route::prefix('mosaics')->group(function () {
        Route::get('/', [MosaicController::class, 'index']);
        Route::get('/{id}', [MosaicController::class, 'show']);
        Route::get('/by-title/{title}', [MosaicController::class, 'showByTitle']);
        Route::get('/by-title/{title}/with-key', [MosaicController::class, 'showByTitleWithApiKey']);
        Route::post('/{mosaicItem}/split', [MosaicController::class, 'split']);
    });
});

// User-specific routes (only require Sanctum)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/albums', [AlbumController::class, 'userAlbums']);
        Route::get('/mosaics', [MosaicController::class, 'userMosaics']);
    });
});

// Media upload route with specific rate limiting and file size checks
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->middleware('file.size:10240') // 10MB limit
        ->name('media.upload');
});
