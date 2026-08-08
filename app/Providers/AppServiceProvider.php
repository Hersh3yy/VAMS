<?php

namespace App\Providers;

use App\Models\Album;
use App\Models\Entry;
use App\Models\Mosaic;
use App\Policies\AlbumPolicy;
use App\Policies\EntryPolicy;
use App\Policies\MosaicPolicy;
use Illuminate\Support\Facades\Gate;
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
        $shouldForceHttps = filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)
            || request()->header('X-Forwarded-Proto') === 'https'
            || (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://'));

        if ($shouldForceHttps) {
            URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }

        Vite::prefetch(concurrency: 3);
        Gate::policy(Album::class, AlbumPolicy::class);
        Gate::policy(Mosaic::class, MosaicPolicy::class);
        Gate::policy(Entry::class, EntryPolicy::class);
    }
}
