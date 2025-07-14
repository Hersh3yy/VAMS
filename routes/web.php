<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\MosaicController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirect root to dashboard (with auth protection)
Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes - FRONTEND PAGES ONLY
Route::middleware(['auth', 'verified'])->group(function () {

    // Album Frontend Pages
    Route::get('/albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::get('/albums/create', [AlbumController::class, 'create'])->name('albums.create');
    Route::get('/albums/{album}', [AlbumController::class, 'show'])->name('albums.show');
    Route::get('/albums/{album}/edit', [AlbumController::class, 'edit'])->name('albums.edit');

    // Mosaic Frontend Pages  
    Route::get('/mosaics', [MosaicController::class, 'index'])->name('mosaics.index');
    Route::get('/mosaics/create', [MosaicController::class, 'create'])->name('mosaics.create');
    Route::get('/mosaics/{mosaic}', [MosaicController::class, 'show'])->name('mosaics.show');
    Route::get('/mosaics/{mosaic}/edit', [MosaicController::class, 'edit'])->name('mosaics.edit');

    // Profile Frontend Pages
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

// CRUD operations (for named routes needed by frontend)
Route::middleware(['auth', 'verified'])->group(function () {
    // Album CRUD operations
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::patch('/albums/{album}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy'])->name('albums.destroy');

    // Album Images CRUD operations  
    Route::post('/albums/{album}/images', [\App\Http\Controllers\AlbumImageController::class, 'store'])->name('albums.images.store');
    Route::post('/albums/{album}/images/video', [\App\Http\Controllers\AlbumImageController::class, 'storeVideo'])->name('albums.images.store-video');
    Route::patch('/albums/{album}/images/{image}', [\App\Http\Controllers\AlbumImageController::class, 'update'])->name('albums.images.update');
    Route::delete('/albums/{album}/images/{image}', [\App\Http\Controllers\AlbumImageController::class, 'destroy'])->name('albums.images.destroy');
    Route::patch('/albums/{album}/reorder', [\App\Http\Controllers\AlbumImageController::class, 'reorder'])->name('albums.images.reorder');

    // Generic Media Upload (for future entities like blog posts, news articles, etc.)
    Route::post('/media/upload', [\App\Http\Controllers\MediaUploadController::class, 'upload'])->name('media.upload');

    // Mosaic CRUD operations
    Route::post('/mosaics', [MosaicController::class, 'store'])->name('mosaics.store');
    Route::patch('/mosaics/{mosaic}', [MosaicController::class, 'update'])->name('mosaics.update');
    Route::delete('/mosaics/{mosaic}', [MosaicController::class, 'destroy'])->name('mosaics.destroy');

    // Mosaic Media Upload
    Route::post('/mosaics/{mosaic}/media', [MosaicController::class, 'storeMedia'])->name('mosaics.media.upload');
});

// Profile routes (these can stay as they are minimal)
Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::patch('/theme', [ProfileController::class, 'updateTheme'])->name('theme.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

        // Logo management
        Route::post('/logo', [ProfileController::class, 'updateLogo'])->name('profile.logo.update');
        Route::delete('/logo', [ProfileController::class, 'destroyLogo'])->name('profile.logo.destroy');
    });
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    // User management
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/approve', [App\Http\Controllers\Admin\UserController::class, 'approve'])->name('users.approve');
    Route::post('users/{user}/impersonate', [App\Http\Controllers\Admin\UserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('users/{user}/regenerate-api-key', [App\Http\Controllers\Admin\UserController::class, 'regenerateApiKey'])->name('users.regenerate-api-key');
});

// Route for stopping impersonation - accessible to anyone while impersonating
Route::post('admin/stop-impersonating', [App\Http\Controllers\Admin\UserController::class, 'stopImpersonating'])
    ->middleware(['auth'])
    ->name('admin.stop-impersonating');

// API Testing route (for development)
Route::get('/test-api', function () {
    return view('test-api');
})->middleware(['auth', 'admin'])->name('test-api');

require __DIR__ . '/auth.php';
