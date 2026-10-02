<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Traits\Features;

use TetesDePioche\LaravelModularApi\Features\ObfuscatedIdEncoder;

trait ObfuscatedId
{
    public static bool $useObfuscatedIds = true;

    /**
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     */
    public function encode(mixed $value, array $options = []): mixed
    {
        return $this->isObfuscatedIdsFeatureEnabled()
            ? $this->obfuscatedIdEncoder()->encode($value, $options)
            : $value;
    }

    /**
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     */
    public function decode(mixed $value, array $options = []): mixed
    {
        return $this->isObfuscatedIdsFeatureEnabled()
            ? $this->obfuscatedIdEncoder()->decode($value, $options)
            : $value;
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     * @return array<int, mixed>
     */
    public function encodeMany(array $values, array $options = []): array
    {
        return array_map(fn(mixed $value): mixed => $this->encode($value, $options), $values);
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array{alphabet?: string, key?: string, min_length?: int}  $options
     * @return array<int, mixed>
     */
    public function decodeMany(array $values, array $options = []): array
    {
        return array_map(fn(mixed $value): mixed => $this->decode($value, $options), $values);
    }

    private function obfuscatedIdEncoder(): ObfuscatedIdEncoder
    {
        return app(ObfuscatedIdEncoder::class);
    }

    private function isObfuscatedIdsFeatureEnabled(): bool
    {
        return static::$useObfuscatedIds && $this->obfuscatedIdEncoder()->isEnabled();
    }
}
