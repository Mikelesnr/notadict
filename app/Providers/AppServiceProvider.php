<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\URL;

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
        Vite::prefetch(concurrency: 3);

        // Enforce HTTPS links, redirects, and assets in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Mail::extend('gmail_api', function (array $config = []) {
            return new GmailApiTransport();
        });
    }
}
