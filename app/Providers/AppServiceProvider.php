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
    // Force HTTPS for ngrok and production
    if (str_contains(config('app.url', ''), 'ngrok') || 
        $this->app->environment('production')) {
        URL::forceScheme('https');
    }
    
    // CRITICAL: Force root URL from config
    URL::forceRootUrl(config('app.url'));
    
    // Additional ngrok-specific fix
    if (str_contains(config('app.url', ''), 'ngrok')) {
        $this->app['request']->server->set('HTTPS', 'on');
    }
}
}