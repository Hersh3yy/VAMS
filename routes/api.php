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

// Protected routes that require both Sanctum and API key
Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
    // Album routes
    Route::get('/albums', [AlbumController::class, 'index']);
    Route::get('/albums/{id}', [AlbumController::class, 'show']);
    Route::get('/albums/title/{title}', [AlbumController::class, 'showByTitle']);
    Route::get('/albums/title/{title}/with-key', [AlbumController::class, 'showByTitleWithApiKey']);

    // Mosaic routes
    Route::get('/mosaics', [MosaicController::class, 'index']);
    Route::get('/mosaics/{id}', [MosaicController::class, 'show']);
    Route::get('/mosaics/title/{title}', [MosaicController::class, 'showByTitle']);
    Route::get('/mosaics/title/{title}/with-key', [MosaicController::class, 'showByTitleWithApiKey']);
    Route::post('/mosaics/{mosaicItem}/split', [MosaicController::class, 'split']);
});

// User-specific routes (only require Sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/albums', [AlbumController::class, 'userAlbums']);
    Route::get('/user/mosaics', [MosaicController::class, 'userMosaics']);
});

// Media upload route
Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
