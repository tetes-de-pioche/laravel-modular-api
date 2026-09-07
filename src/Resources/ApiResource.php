<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Str;
use LaravelModularApi;
use TetesDePioche\LaravelModularApi\Traits\Features\ObfuscatedId;
use TetesDePioche\LaravelModularApi\Traits\Resources\HasLinks;

class ApiResource extends JsonApiResource
{
    use HasLinks;
    use ObfuscatedId;

    public function toId(Request $request): string
    {
        $id = (string) $this->resource->getKey();

        if (! $this->isObfuscatedIdsFeatureEnabled()) {
            return $id;
        }

        return $this->encode($id);
    }

    public function toType(Request $request): ?string
    {
        return config('modular-api.api.resource.custom_type_resolver')
            ? parent::toType($request)
            : Str::camel(LaravelModularApi::domainFromClass($this))
            . LaravelModularApi::resourceFromClass($this);
    }
}
