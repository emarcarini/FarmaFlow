<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\WhatsApp\EvolutionApiService::class, function () {
            return new \App\Services\WhatsApp\EvolutionApiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
        
        $appUrl = config('app.url');
        if ($appUrl && $appUrl !== 'http://localhost') {
            if (str_starts_with($appUrl, 'http://')) {
                $appUrl = str_replace('http://', 'https://', $appUrl);
            }
            URL::forceRootUrl($appUrl);
        }
    }
}
