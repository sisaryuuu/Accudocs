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
        $this->app->singleton(IprogSMSService::class, function ($app) {
            return new IprogSMSService();
        });

        $this->app->bind('iprogsms', function ($app) {
            return new IprogSMSService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Always force HTTPS (most reliable solution)
       if ($this->app->environment('local')) {
        \URL::forceScheme('https');
    }
        
        // Alternative: Check environment
        // if ($this->app->environment('production') || 
        //     str_contains(env('APP_URL', ''), 'ngrok-free.dev')) {
        //     URL::forceScheme('https');
        // }
    }
}