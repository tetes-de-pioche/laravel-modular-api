<?php

namespace TetesDePioche\LaravelModularApi\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdentifyApiType
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('modular-api.api.routing.enable_type_prefix')) {
            $apiType = Str::before(
                Str::replaceFirst(
                    config('modular-api.api.routing.url_prefix'),
                    '',
                    $request->getPathInfo()
                ),
                '/'
            );

            $request->mergeIfMissing(['api_type' => $apiType]);
        }

        return $next($request);
    }
}
