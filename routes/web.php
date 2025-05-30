<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\AlbumImageController;
use App\Http\Controllers\MosaicController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public welcome page for guests
Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard (homepage)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Albums
    Route::resource('albums', AlbumController::class);

    // Album Images
    Route::prefix('album-images')->name('album-images.')->group(function () {
        Route::post('/', [AlbumImageController::class, 'store'])->name('store');
        Route::post('/reorder', [AlbumImageController::class, 'reorder'])->name('reorder');
        Route::post('/store-video', [AlbumImageController::class, 'storeVideo'])->name('store-video');
        Route::resource('/', AlbumImageController::class)->except(['store']);
    });
    
    // Mosaics
    Route::resource('mosaics', MosaicController::class);

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
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
