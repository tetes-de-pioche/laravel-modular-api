<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TetesDePioche\LaravelModularApi\Traits\Features\ObfuscatedId;

class BaseRequest extends FormRequest
{
    use ObfuscatedId;

    public array $routeParameters = [];

    public array $encodedInputs = [];

    public function rules(): array
    {
        return [];
    }

    /**
     * @see FormRequest::all()
     */
    public function all($keys = null): array
    {
        $input = parent::all($keys);

        $this->handleRouteId();

        $input = $this->mergeRouteParametersWithInput($input);
        $input = $this->mergeJsonApiDataWithInput($input);
        $input = $this->decodeObfuscatedIds($input);

        return $input;
    }

    public function inputMerged($key = null, $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    private function handleRouteId(): bool
    {
        if ($this->route('id') !== null) {
            if (! in_array('id', $this->routeParameters)) {
                $this->routeParameters[] = 'id';
            }
        }

        if ($this->isObfuscatedIdsFeatureEnabled()) {
            $resourceClasspath = Str::replaceLast(
                'Http\\Requests',
                'Resources',
                get_class($this)
            );

            $resourceClasspath = Str::replaceLast(
                '\\' . Str::afterLast($resourceClasspath, '\\'),
                'Resource',
                $resourceClasspath
            );

            if (! class_exists($resourceClasspath)) {
                $resourceClasspath = Str::before(
                    get_class($this),
                    'Http\\Requests'
                );
                $resourceClasspath .= 'Resources\\' . Str::afterLast(Str::beforeLast($resourceClasspath, '\\'), '\\') . 'Resource';
            }

            if (class_exists($resourceClasspath)) {
                if ($resourceClasspath::$useObfuscatedIds === true) {
                    if (! in_array('id', $this->encodedInputs)) {
                        $this->encodedInputs[] = 'id';
                    }

                    return true;
                }
            }
        }

        return false;
    }

    private function mergeRouteParametersWithInput(array $input): array
    {
        if (! empty($this->routeParameters)) {
            foreach ($this->routeParameters as $parameter) {
                $input[$parameter] = $this->route($parameter);
            }
        }

        return $input;
    }

    private function mergeJsonApiDataWithInput(array $input): array
    {
        if (isset($input['data'])) {
            if (isset($input['data']['attributes']) && is_array($input['data']['attributes'])) {
                $input = array_merge_recursive($input, $input['data']['attributes']);
            }

            if (isset($input['data']['type'])) {
                $input['type'] = $input['data']['type'];
            }
        }

        return $input;
    }

    protected function decodeObfuscatedIds(array $input): array
    {
        if ($this->isObfuscatedIdsFeatureEnabled() && ! empty($this->encodedInputs)) {
            foreach ($this->encodedInputs as $key) {
                $input = $this->decodeObfuscatedIdInput($input, explode('.', $key), $key);
            }
        }

        return $input;
    }

    private function decodeObfuscatedIdInput(mixed $input, array $keyList, string $currentKey): mixed
    {
        if (empty($keyList)) {
            return empty($input)
                ? $input
                : $this->decode($input);
        }

        $field = array_shift($keyList);

        if (in_array($field, ['*', ','])) {
            if ($field === '*') {
                $input = Arr::wrap($input);
            } elseif ($field === ',') {
                $input = explode(',', $input);
            }

            foreach ($input as $key => $value) {
                $input[$key] = $this->decodeObfuscatedIdInput($value, $keyList, $currentKey . '[' . $key . ']');
            }

            return $input;
        }

        if (! array_key_exists($field, Arr::wrap($input))) {
            return $input;
        }

        $input[$field] = $this->decodeObfuscatedIdInput($input[$field], $keyList, $field);

        return $input;
    }
}
