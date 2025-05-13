<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\AlbumImageController;
use App\Http\Controllers\MosaicController;
use App\Http\Controllers\MosaicItemController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Album;
use Illuminate\Support\Facades\Schema;

// Main dashboard as homepage
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        // Get authenticated user
        $recentAlbums = [];
        
        try {
            // Get albums if user is authenticated
            if ($user = Auth::user()) {
                // Log the user ID for debugging
                \Illuminate\Support\Facades\Log::info('Dashboard - User ID: ' . $user->id);
                
                // Query recent albums
                $recentAlbums = Album::where('user_id', $user->id)
                    ->withCount('images')
                    ->orderBy('updated_at', 'desc')
                    ->take(3)
                    ->get();
                
                \Illuminate\Support\Facades\Log::info('Dashboard - Found albums: ' . $recentAlbums->count());
                
                // Log album details for debugging
                foreach ($recentAlbums as $album) {
                    \Illuminate\Support\Facades\Log::info("Album: {$album->title} (ID: {$album->id})");
                }
            }
        } catch (\Exception $e) {
            // Log error but continue
            \Illuminate\Support\Facades\Log::error('Error fetching recent albums: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
        }
        
        return Inertia::render('Dashboard', [
            'recentAlbums' => $recentAlbums,
        ]);
    })->name('dashboard');
});

// Public welcome page for guests
Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Logo management
    Route::post('/profile/logo', [ProfileController::class, 'updateLogo'])->name('profile.logo.update');
    Route::delete('/profile/logo', [ProfileController::class, 'destroyLogo'])->name('profile.logo.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::get('/albums/create', [AlbumController::class, 'create'])->name('albums.create');
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::get('/albums/{album}', [AlbumController::class, 'show'])->name('albums.show');
    Route::get('/albums/{album}/edit', [AlbumController::class, 'edit'])->name('albums.edit');
    Route::put('/albums/{album}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{album}', [AlbumController::class, 'destroy'])->name('albums.destroy');

    // Album Images
    Route::post('/album-images', [AlbumImageController::class, 'store'])->name('album-images.store');
    Route::post('/album-images/reorder', [AlbumImageController::class, 'reorder'])->name('album-images.reorder');
    Route::post('/album-images/store-video', [AlbumImageController::class, 'storeVideo'])->name('album-images.store-video');
    
    // Resources
    Route::resource('mosaics', MosaicController::class);
    Route::resource('mosaic-items', MosaicItemController::class);
    Route::post('/mosaic-items/{mosaicItem}/split', [MosaicItemController::class, 'split'])->name('mosaic-items.split');
    Route::resource('album-images', AlbumImageController::class);
});

// Add admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
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
