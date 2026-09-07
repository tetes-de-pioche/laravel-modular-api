<?php

namespace TetesDePioche\LaravelModularApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TetesDePioche\LaravelModularApi\LaravelModularApi
 */
class LaravelModularApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TetesDePioche\LaravelModularApi\LaravelModularApi::class;
    }
}
