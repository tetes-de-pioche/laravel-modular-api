<?php

namespace TetesDePioche\LaravelModularApi\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use TetesDePioche\LaravelModularApi\Exceptions\LocalizationLocaleUnsupportedException;

class Localization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('modular-api.features.localization.enabled')) {
            $locale = $this->locale($this->fromHeader($request));

            if (App::currentLocale() !== $locale) {
                App::setLocale($locale);
            }

            $response = $next($request);
            $response->headers->set(config('modular-api.features.localization.request_header'), $locale);

            return $response;
        }

        return $next($request);
    }

    private function fromHeader(Request $request): string
    {
        return $request->hasHeader(config('modular-api.features.localization.request_header'))
            ? $request->header(config('modular-api.features.localization.request_header'))
            : config('app.locale');
    }

    private function locale(string $requestHeaderLocale): string
    {
        $localeList = config('modular-api.features.localization.locales');

        if (! is_array($localeList)) {
            $localeList = explode(',', $localeList);
        }

        foreach (explode(',', $requestHeaderLocale) as $requestLocale) {
            $locale = explode(';', $requestLocale)[0];

            if (in_array($locale, $localeList)) {
                return $locale;
            }

            if (Str::contains($locale, '-')) {
                $baseLocale = explode('-', $locale)[0];
                if (in_array($baseLocale, $localeList)) {
                    return $baseLocale;
                }
            }
        }

        throw new LocalizationLocaleUnsupportedException;
    }
}
