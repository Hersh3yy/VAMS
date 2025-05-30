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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// All API routes require API key authentication
Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
    // Album endpoints
    Route::prefix('albums')->group(function () {
        Route::get('/', [AlbumController::class, 'index']);
        Route::get('/{album}', [AlbumController::class, 'show']);
        Route::get('/title/{title}', [AlbumController::class, 'showByTitle']);
        Route::get('/title/{title}/with-key', [AlbumController::class, 'showByTitleWithApiKey']);
    });
    
    // Mosaic endpoints
    Route::prefix('mosaics')->group(function () {
        Route::get('/', [MosaicController::class, 'index']);
        Route::get('/{mosaic}', [MosaicController::class, 'show']);
        Route::get('/title/{title}', [MosaicController::class, 'showByTitle']);
        Route::get('/title/{title}/with-key', [MosaicController::class, 'showByTitleWithApiKey']);
        Route::post('/items/{mosaicItem}/split', [MosaicController::class, 'split']);
    });

    // Legacy Strapi-compatible endpoint
    Route::get('/{albumName}', [AlbumController::class, 'showStrapiFormat']);

    // Media upload route
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
});
