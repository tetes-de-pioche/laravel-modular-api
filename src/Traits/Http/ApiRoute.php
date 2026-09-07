<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Traits\Http;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LaravelModularApi;
use Symfony\Component\Finder\SplFileInfo;
use TetesDePioche\LaravelModularApi\Http\Middlewares\IdentifyApiType;
use TetesDePioche\LaravelModularApi\Http\Middlewares\Localization;

trait ApiRoute
{
    public function loadApiRoutes(): void
    {
        foreach (LaravelModularApi::servicePathList() as $servicePath) {
            $this->loadServiceApiRoutes($servicePath);
        }
    }

    public function apiRouteGroup(?SplFileInfo $file = null, ?string $prefix = null): array
    {
        $prefixList = $this->apiPrefixesFromFile($file);

        return [
            'middleware' => $this->apiMiddlewares(),
            'domain' => LaravelModularApi::apiUrl(),
            'prefix' => LaravelModularApi::apiUrlPrefix()
                . ($prefix !== null
                    ? $prefix
                    : implode('/', $prefixList)
                ),
            'meta' => $prefixList,
        ];
    }

    private function loadServiceApiRoutes(string $servicePath): void
    {
        $routesPath = $servicePath . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Endpoints';

        if (File::isDirectory($routesPath)) {
            $files = Arr::sort(
                Arr::where(
                    File::allFiles($routesPath),
                    fn($file) => $file->getExtension() === 'php'
                ),
                fn($file) => $file->getFilename()
            );

            foreach ($files as $file) {
                $this->loadServiceApiRoutesFromFile($file);
            }
        }
    }

    private function loadServiceApiRoutesFromFile(SplFileInfo $file): void
    {
        Route::group($this->apiRouteGroup(file: $file), function () use ($file) {
            require $file->getPathname();
        });
    }

    private function apiMiddlewares(): array
    {
        return array_filter([
            'api',
            $this->middlewareRateLimiter(),
            $this->middlewareIdentifyApiType(),
            $this->middlewareLocalization(),
        ]);
    }

    private function middlewareRateLimiter(): ?string
    {
        if (config('modular-api.features.rate_limiting.enabled')) {
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinutes(
                    config('modular-api.features.rate_limiting.expires'),
                    config('modular-api.features.rate_limiting.attempts')
                )->by($request->user()?->id ?: $request->ip());
            });

            return 'throttle:api';
        }

        return null;
    }

    private function middlewareLocalization(): ?string
    {
        return config('modular-api.features.localization.enabled')
            ? Localization::class
            : null;
    }

    private function middlewareIdentifyApiType(): ?string
    {
        return config('modular-api.api.routing.enable_type_prefix')
            ? IdentifyApiType::class
            : null;
    }

    private function apiPrefixesFromFile(?SplFileInfo $file): array
    {
        $prefixList = [];

        if (! config('modular-api.api.routing.enable_version_prefix') && ! config('modular-api.api.routing.enable_type_prefix')) {
            return $prefixList;
        }

        $filenameExploded = explode('.', $file->getFilenameWithoutExtension());

        if (config('modular-api.api.routing.enable_type_prefix') && count($filenameExploded) > 0) {
            $prefixList['type'] = array_pop($filenameExploded);
        }

        if (config('modular-api.api.routing.enable_version_prefix') && count($filenameExploded) > 0) {
            $prefixList['version'] = array_pop($filenameExploded);
        }

        return $prefixList;
    }

    public function registerApiMacros(): void
    {
        Route::macro('authenticatedEndpoint', function ($domain, $service, $resource = null, $action = '', $options = []) {
            $options['auth'] = true;

            Route::endpoint(
                domain: $domain,
                service: $service,
                resource: $resource,
                action: $action,
                options: $options,
            );
        });

        Route::macro('guestEndpoint', function ($domain, $service, $resource = null, $action = '', $options = []) {
            $options['auth'] = false;

            Route::endpoint(
                domain: $domain,
                service: $service,
                resource: $resource,
                action: $action,
                options: $options,
            );
        });

        Route::macro('endpoint', function ($domain, $service, $resource = null, $action = '', $options = []) {
            $options['extraActions'] = [
                $action => [
                    'method' => $options['method'] ?? 'GET',
                    'uri' => $options['uri'] ?? $action,
                ],
            ];
            $options['actions'] = [];

            Route::endpoints(
                domain: $domain,
                service: $service,
                resource: $resource,
                options: $options,
            );
        });

        Route::macro('authenticatedEndpoints', function ($domain, $service, $resource = null, $options = []) {
            $options['auth'] = true;

            Route::endpoints(
                domain: $domain,
                service: $service,
                resource: $resource,
                options: $options,
            );
        });

        Route::macro('guestEndpoints', function ($domain, $service, $resource = null, $options = []) {
            $options['auth'] = false;

            Route::endpoints(
                domain: $domain,
                service: $service,
                resource: $resource,
                options: $options,
            );
        });

        Route::macro('endpoints', function ($domain, $service, $resource = null, $options = []) {
            $apiType = null;
            if ($this->hasGroupStack()) {
                $apiType = data_get($this->getGroupStack(), '0.meta.type');
            }

            $actionDefaultList = [
                'get' => ['method' => 'get'],
                'find' => ['method' => 'get', 'uri' => '{' . ($options['identifier'] ?? 'id') . '}'],
                'create' => ['method' => 'post'],
                'update' => ['method' => 'patch', 'uri' => '{' . ($options['identifier'] ?? 'id') . '}'],
                'destroy' => ['method' => 'delete', 'uri' => '{' . ($options['identifier'] ?? 'id') . '}'],
            ];

            $actionList = isset($options['actions'])
                ? Arr::only($actionDefaultList, $options['actions'])
                : $actionDefaultList;

            $routePrefix = LaravelModularApi::apiRoutePrefix();
            if ($apiType !== null) {
                $routePrefix .= $apiType . '.';
            }

            $authMiddleware = [];
            if (isset($options['auth'])) {
                if ($options['auth'] === true) {
                    $authMiddleware = 'auth:api' . ($apiType ? '-' . $apiType : '');
                } elseif ($options['auth'] === false) {
                    $authMiddleware = 'guest:api' . ($apiType ? '-' . $apiType : '');
                }
            }

            $domainName = (isset($options['uriDomain']) && ! empty($options['uriDomain']))
                ? $options['uriDomain']
                : ((($options['withoutDomain'] ?? false) === true)
                    ? ''
                    : LaravelModularApi::domainSlug($domain)
                );

            $endpointName = (isset($options['uriEndpoint']) && ! empty($options['uriEndpoint']))
                ? $options['uriEndpoint']
                : ((($options['withoutService'] ?? false) === true)
                    ? ''
                    : LaravelModularApi::resourceSlug($resource ?? $service)
                );

            $resourceName = Str::studly($resource ?? $service);

            $controllerClassPath = LaravelModularApi::servicesClassPathRoot()
                . Str::studly($domain)
                . '\\' . Str::studly($service)
                . '\\Http\\Controllers\\';

            Route::prefix($domainName)
                ->middleware(array_merge(Arr::wrap($authMiddleware), ($options['middlewares'] ?? [])))
                ->name($routePrefix . (empty($domainName) ? '' : $domainName . '.'))
                ->group(function () use ($options, $actionList, $endpointName, $resourceName, $controllerClassPath) {
                    Route::prefix($endpointName)
                        ->name((empty($endpointName) ? '' : $endpointName . '.'))
                        ->group(function () use ($options, $actionList, $resourceName, $controllerClassPath) {
                            if (is_array($options['extraActions'] ?? null) && count($options['extraActions']) > 0) {
                                foreach ($options['extraActions'] as $actionName => $actionArgs) {
                                    Route::{$actionArgs['method']}(
                                        ($actionArgs['uri'] ?? ''),
                                        $controllerClassPath . $resourceName . Str::studly($actionName) . 'Controller'
                                    )->name($actionName);
                                }
                            }

                            foreach ($actionList as $actionName => $actionArgs) {
                                Route::{$actionArgs['method']}(
                                    ($actionArgs['uri'] ?? ''),
                                    $controllerClassPath . $resourceName . Str::studly($actionName) . 'Controller'
                                )->name($actionName);
                            }
                        });
                });
        });
    }
}
