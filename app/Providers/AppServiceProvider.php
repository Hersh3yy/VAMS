<?php

namespace App\Providers;

use App\Models\Album;
use App\Models\Entry;
use App\Policies\AlbumPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set PHP upload limits (only if current values are lower)
        // This ensures we have adequate limits for image uploads (20MB)
        $requiredUploadMax = 20 * 1024 * 1024; // 20MB in bytes
        $requiredPostMax = 25 * 1024 * 1024; // 25MB in bytes
        
        $currentUploadMax = $this->convertToBytes(ini_get('upload_max_filesize'));
        $currentPostMax = $this->convertToBytes(ini_get('post_max_size'));

        if ($currentUploadMax < $requiredUploadMax) {
            ini_set('upload_max_filesize', '20M');
        }
        
        if ($currentPostMax < $requiredPostMax) {
            ini_set('post_max_size', '25M');
        }

        // Ensure adequate memory and execution time for image processing
        $currentMemory = $this->convertToBytes(ini_get('memory_limit'));
        $requiredMemory = 256 * 1024 * 1024; // 256MB
        if ($currentMemory < $requiredMemory) {
            ini_set('memory_limit', '256M');
        }

        // Force HTTPS in production or when behind a proxy
        if (app()->isProduction() || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }

        Vite::prefetch(concurrency: 3);
        Gate::policy(Album::class, AlbumPolicy::class);

        // Configure route model binding for entries
        Route::bind('entry', function ($value) {
            return Entry::where('id', $value)->firstOrFail();
        });
    }

    /**
     * Convert PHP ini size format to bytes (handles K, M, G)
     */
    private function convertToBytes(string $size): int
    {
        $size = trim($size);
        if (empty($size)) {
            return 0;
        }
        
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
