<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi;

use Illuminate\Support\ServiceProvider;
use TetesDePioche\LaravelModularApi\Exceptions\Handler as ExceptionHandler;
use TetesDePioche\LaravelModularApi\Features\ObfuscatedIdEncoder;
use TetesDePioche\LaravelModularApi\Providers\RouteServiceProvider;
use TetesDePioche\LaravelModularApi\Traits\Config\HasConfigs;
use TetesDePioche\LaravelModularApi\Traits\Data\HasMigrations;
use TetesDePioche\LaravelModularApi\Traits\Providers\HasProviders;
use TetesDePioche\LaravelModularApi\Traits\Views\HasViews;

class LaravelModularApiServiceProvider extends ServiceProvider
{
    use HasConfigs, HasMigrations, HasProviders, HasViews;

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/modular-api.php',
            'modular-api'
        );

        $this->registerExceptionHandler();
        $this->registerObfuscatedIdEncoder();

        $this->app->register(RouteServiceProvider::class);
        $this->loadConfigs();
        $this->loadProviders();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/modular-api.php' => config_path('modular-api.php'),
            ], 'modular-api-config');

            $this->loadMigrations();
        }

        $this->loadViews();
    }

    private function registerExceptionHandler(): void
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            ExceptionHandler::class
        );
    }

    /**
     * The encoder memoizes its Sqids instances, which are derived from the
     * configuration and therefore safe to share across requests. Bind another
     * implementation to change how identifiers are obfuscated.
     */
    private function registerObfuscatedIdEncoder(): void
    {
        $this->app->singleton(ObfuscatedIdEncoder::class);
    }
}
