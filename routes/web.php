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

// Profile routes (these can stay as they are minimal)
Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        
        // Logo management
        Route::post('/logo', [ProfileController::class, 'updateLogo'])->name('logo.update');
        Route::delete('/logo', [ProfileController::class, 'destroyLogo'])->name('logo.destroy');
    });
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // User management
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/approve', [App\Http\Controllers\Admin\UserController::class, 'approve'])->name('users.approve');
    Route::post('users/{user}/impersonate', [App\Http\Controllers\Admin\UserController::class, 'impersonate'])->name('users.impersonate');
});

// Route for stopping impersonation - accessible to anyone while impersonating
Route::post('admin/stop-impersonating', [App\Http\Controllers\Admin\UserController::class, 'stopImpersonating'])
    ->middleware(['auth'])
    ->name('admin.stop-impersonating');

require __DIR__.'/auth.php';
