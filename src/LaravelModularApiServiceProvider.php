<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi;

use Illuminate\Support\ServiceProvider;
use TetesDePioche\LaravelModularApi\Providers\RouteServiceProvider;

class LaravelModularApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/modular-api.php',
            'modular-api'
        );

        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/modular-api.php' => config_path('modular-api.php'),
            ], 'modular-api-config');
        }
    }
}
