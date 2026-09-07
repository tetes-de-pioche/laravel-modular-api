<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use TetesDePioche\LaravelModularApi\Traits\Http\ApiRoute;
use TetesDePioche\LaravelModularApi\Traits\Http\WebRoute;

class RouteServiceProvider extends ServiceProvider
{
    use ApiRoute, WebRoute;

    public function boot(): void
    {
        $this->registerApiMacros();
        $this->loadApiRoutes();

        $this->loadWebRoutes();
    }
}
