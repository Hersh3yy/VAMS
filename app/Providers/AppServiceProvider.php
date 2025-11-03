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
}
