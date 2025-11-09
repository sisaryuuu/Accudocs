<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\DvRequest;

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
        // ✅ Always force HTTPS (most reliable solution)
        URL::forceScheme('http');
        
        // Alternative: Check environment
        // if ($this->app->environment('production') || 
        //     str_contains(env('APP_URL', ''), 'ngrok-free.dev')) {
        //     URL::forceScheme('https');
        // }
    }
}