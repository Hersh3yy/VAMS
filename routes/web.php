<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MosaicController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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

    // Entry Frontend Pages
    Route::get('/entries', [\App\Http\Controllers\EntryController::class, 'index'])->name('entries.index');
    Route::get('/entries/create', [\App\Http\Controllers\EntryController::class, 'create'])->name('entries.create');
    Route::get('/entries/{entry}', [\App\Http\Controllers\EntryController::class, 'show'])->name('entries.show');
    Route::get('/entries/{entry}/edit', [\App\Http\Controllers\EntryController::class, 'edit'])->name('entries.edit');

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

    // Entry CRUD operations
    Route::post('/entries', [\App\Http\Controllers\EntryController::class, 'store'])->name('entries.store');
    Route::patch('/entries/reorder', [\App\Http\Controllers\EntryController::class, 'reorder'])->name('entries.reorder');
    Route::patch('/entries/{entry}', [\App\Http\Controllers\EntryController::class, 'updateEntry'])->name('entries.update');
    Route::delete('/entries/{entry}', [\App\Http\Controllers\EntryController::class, 'destroy'])->name('entries.destroy');

    // Mosaic CRUD operations
    Route::post('/mosaics', [MosaicController::class, 'store'])->name('mosaics.store');
    Route::patch('/mosaics/{mosaic}', [MosaicController::class, 'update'])->name('mosaics.update');
    Route::delete('/mosaics/{mosaic}', [MosaicController::class, 'destroy'])->name('mosaics.destroy');

    // Mosaic Item Management
    Route::post('/mosaics/{mosaic}/items', [MosaicController::class, 'storeItem'])->name('mosaics.items.store');
    Route::put('/mosaics/{mosaic}/items/{item}', [MosaicController::class, 'updateItem'])->name('mosaics.items.update');
    Route::delete('/mosaics/{mosaic}/items/{item}', [MosaicController::class, 'destroyItem'])->name('mosaics.items.destroy');
    Route::patch('/mosaics/{mosaic}/items/reorder', [MosaicController::class, 'reorderItems'])->name('mosaics.items.reorder');

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
    // Entry type management
    Route::get('/entry-types', [App\Http\Controllers\Admin\EntryTypeController::class, 'index'])->name('entry-types.index');
    Route::post('/entry-types', [App\Http\Controllers\Admin\EntryTypeController::class, 'store'])->name('entry-types.store');
    Route::patch('/entry-types/{entryType}', [App\Http\Controllers\Admin\EntryTypeController::class, 'update'])->name('entry-types.update');
    Route::delete('/entry-types/{entryType}', [App\Http\Controllers\Admin\EntryTypeController::class, 'destroy'])->name('entry-types.destroy');
});

// Route for stopping impersonation - accessible to anyone while impersonating
Route::post('admin/stop-impersonating', [App\Http\Controllers\Admin\UserController::class, 'stopImpersonating'])
    ->middleware(['auth'])
    ->name('admin.stop-impersonating');

// CSRF Token refresh route
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->middleware(['web']);

require __DIR__.'/auth.php';
