<?php

namespace App\Providers;

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
        // Trust the proxy headers for GitHub Codespaces
        // This prevents Laravel from adding port 8000 to redirects
        \Illuminate\Support\Facades\URL::forceScheme('https');
        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
    }
}
