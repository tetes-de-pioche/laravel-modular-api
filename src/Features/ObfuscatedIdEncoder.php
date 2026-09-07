<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Features;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sqids\Sqids;
use TetesDePioche\LaravelModularApi\Exceptions\FeatureInvalidException;
use TetesDePioche\LaravelModularApi\Exceptions\ObfuscatedIdInvalidException;

class ObfuscatedIdEncoder
{
    /**
     * Memoized Sqids instances, keyed by the settings they were built from.
     *
     * Building a Sqids instance shuffles the alphabet and compiles the blocklist
     * regex, so instances are kept for the lifetime of the container instead of
     * being rebuilt on every encode/decode call.
     *
     * @var array<string, Sqids>
     */
    private array $instances = [];

    /**
     * @throws FeatureInvalidException when the feature is on but Sqids is missing
     */
    public function isEnabled(): bool
    {
        $enabled = (bool) config('modular-api.features.obfuscated_ids.enabled', false);

        throw_if(
            $enabled && ! class_exists(Sqids::class),
            new FeatureInvalidException('Sqids package is not installed or loaded.')
        );

        return $enabled;
    }

    /**
     * Returns the value untouched when the feature is disabled.
     *
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     */
    public function encode(mixed $value, array $options = []): mixed
    {
        if (! $this->isEnabled()) {
            return $value;
        }

        return $this->sqids($options)->encode(
            array_map(intval(...), Arr::wrap($value))
        );
    }

    /**
     * Returns the value untouched when the feature is disabled, and null for a
     * null or "null" input.
     *
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     *
     * @throws ObfuscatedIdInvalidException
     */
    public function decode(mixed $value, array $options = []): mixed
    {
        if (! $this->isEnabled()) {
            return $value;
        }

        if ($value === null || Str::lower((string) $value) === 'null') {
            return null;
        }

        $sqids = $this->sqids($options);
        $id = (string) $value;

        $numbers = $sqids->decode($id);

        throw_if(empty($numbers), new ObfuscatedIdInvalidException);

        /**
         * Sqids decoding is not canonical: several strings may decode to the
         * same numbers, so any non-canonical representation is rejected.
         */
        throw_if($sqids->encode($numbers) !== $id, new ObfuscatedIdInvalidException);

        return $numbers[0];
    }

    /**
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     */
    private function sqids(array $options = []): Sqids
    {
        $alphabet = $options['alphabet'] ?? (string) config('modular-api.features.obfuscated_ids.alphabet');
        $key = $options['key'] ?? (string) config('modular-api.features.obfuscated_ids.key');
        $minLength = $options['min_length'] ?? (int) config('modular-api.features.obfuscated_ids.min_length');

        $cacheKey = hash('xxh128', $alphabet . "\0" . $key . "\0" . $minLength);

        return $this->instances[$cacheKey] ??= new Sqids(
            alphabet: $this->shuffledAlphabet($alphabet, $key),
            minLength: $minLength,
        );
    }

    /**
     * Sqids has no salt equivalent: project-specific output comes from a
     * deterministic, key-seeded shuffle of the alphabet.
     */
    private function shuffledAlphabet(string $alphabet, #[\SensitiveParameter] string $key): string
    {
        $characters = str_split($alphabet);

        usort($characters, fn(string $a, string $b): int => strcmp(
            hash('sha256', $key . $a),
            hash('sha256', $key . $b),
        ));

        return implode('', $characters);
    }
}
