<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use TetesDePioche\LaravelModularApi\Traits\Features\ObfuscatedId;

class ApiResource extends JsonApiResource
{
    use ObfuscatedId;

    public function toId(Request $request): string
    {
        $id = (string) $this->resource->getKey();

        if (! $this->isObfuscatedIdsFeatureEnabled()) {
            return $id;
        }

        return $this->encode($id);
    }
}
