<?php

namespace TetesDePioche\LaravelModularApi\Traits\Resources;

use Illuminate\Http\Request;
use LaravelModularApi;

trait HasLinks
{
    public bool $hasSelfLink = false;

    /**
     * The self link is added on top of the links the resource already declares
     * through the parent $jsonApiLinks property, rather than replacing them.
     */
    public function toLinks(Request $request): array
    {
        return array_merge(
            parent::toLinks($request),
            $this->hasSelfLink
                ? ['self' => $this->selfRoute($request)]
                : []
        );
    }

    public function selfRoute(Request $request, $resourceName = null, $route = null, $action = null, $prefix = null): string
    {
        if ($route === null) {
            $resourceName = $resourceName ?? LaravelModularApi::resourceFromClass($this);

            $route = LaravelModularApi::domainSlug(
                LaravelModularApi::domainFromClass($this)
            )
                . '.'
                . LaravelModularApi::resourceSlug($resourceName);
        }

        return route(
            ($prefix ?? (LaravelModularApi::apiRoutePrefix() . ($request->has('api_type') ? $request->input('api_type') . '.' : '')))
                . $route
                . ($action ? '.' . $action : '.find'),
            $this->selfRouteId($request)
        );
    }

    public function selfRouteId(Request $request): mixed
    {
        return $this->toId($request);
    }
}
