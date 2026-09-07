<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Traits\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use TetesDePioche\LaravelModularApi\Exceptions\ResourceNotFoundException;
use TetesDePioche\LaravelModularApi\Features\ObfuscatedIdEncoder;

/**
 * Builder macros implementing the JSON:API request contract.
 */
trait HasQueryMacros
{
    private function registerQueryMacros(): void
    {
        $this->registerPaginateMacro();
        $this->registerFindMacro();
    }

    /**
     * Paginate following the JSON:API `page[number]` / `page[size]` convention,
     * preserving every other query parameter in the generated links.
     *
     * `$perPage` and `$maxPerPage` let a caller override the package defaults
     * per resource, for instance to allow a referential to be fetched in one
     * page.
     */
    private function registerPaginateMacro(): void
    {
        Builder::macro('jsonApiPaginate', function (
            Request $request,
            ?int $perPage = null,
            ?int $maxPerPage = null
        ): LengthAwarePaginator {
            $perPage ??= (int) config('modular-api.api.pagination.size_default');
            $maxPerPage ??= (int) config('modular-api.api.pagination.size_max');

            /** @var Builder $this */
            return $this
                ->paginate(
                    perPage: min($request->integer('page.size', $perPage) ?: 1, $maxPerPage),
                    pageName: 'page[number]',
                    page: $request->integer('page.number') ?: 1,
                )
                ->appends(Arr::except($request->query(), 'page'));
        });
    }

    /**
     * Resolve a single record from an identifier as exposed by the API, which
     * means decoding it first when the obfuscated ids feature is enabled.
     */
    private function registerFindMacro(): void
    {
        Builder::macro('jsonApiFind', function (mixed $id): Model {
            /** @var Builder $this */
            $model = $this
                ->whereKey(app(ObfuscatedIdEncoder::class)->decode($id))
                ->first();

            throw_unless($model, new ResourceNotFoundException);

            return $model;
        });
    }
}
