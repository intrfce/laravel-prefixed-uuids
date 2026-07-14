<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Exceptions\InvalidPublicIdException;
use Intrfce\PrefixedUuids\Exceptions\PrefixMismatchException;

/**
 * The public API surface, exposed through the PrefixedId facade. Composes the
 * registry (ADR-0011) with the codec (ADR-0002) and centralises the parse /
 * decode / normalise logic reused by the trait, the custom builder, route
 * binding, and the validation rule.
 */
class PrefixedIdManager
{
    public function __construct(private readonly PrefixIdRegistry $registry) {}

    public function registry(): PrefixIdRegistry
    {
        return $this->registry;
    }

    /**
     * Register prefix => model mappings, morph-map style.
     *
     * @param  array<string, class-string<Model>>  $map
     */
    public function map(array $map): void
    {
        $this->registry->map($map);
    }

    /** Build a Public ID from a raw UUID and an explicit prefix. */
    public function encode(string $uuid, string $prefix): string
    {
        return $prefix.'_'.Codec::encode($uuid);
    }

    /** Build a model's Public ID from its raw UUID key. */
    public function encodeForModel(string $uuid, string $model): string
    {
        return $this->encode($uuid, $this->registry->prefixForModel($model));
    }

    /**
     * Decode any registered Public ID to its raw UUID (no DB access).
     *
     * @throws \Intrfce\PrefixedUuids\Exceptions\UnknownPrefixException
     * @throws \Intrfce\PrefixedUuids\Exceptions\InvalidPublicIdException
     */
    public function decode(string $publicId): string
    {
        [$prefix, $tail] = $this->parse($publicId);

        // Ensures the prefix is known; throws UnknownPrefixException otherwise.
        $this->registry->modelForPrefix($prefix);

        return Codec::decode($tail);
    }

    /**
     * Resolve a Public ID to its model instance via the registry, or null if no
     * such record exists.
     */
    public function resolve(string $publicId): ?Model
    {
        [$prefix, $tail] = $this->parse($publicId);

        $model = $this->registry->modelForPrefix($prefix);
        $uuid = Codec::decode($tail);

        return $model::query()->whereKey($uuid)->first();
    }

    /**
     * Split a Public ID into [prefix, tail]. The tail is always base62 (never
     * contains '_'), so the last underscore is an unambiguous separator even if
     * a prefix itself contains underscores (ADR-0014).
     *
     * @return array{0: string, 1: string}
     */
    public function parse(string $publicId): array
    {
        $pos = strrpos($publicId, '_');

        if ($pos === false || $pos === 0 || $pos === strlen($publicId) - 1) {
            throw InvalidPublicIdException::noSeparator($publicId);
        }

        return [substr($publicId, 0, $pos), substr($publicId, $pos + 1)];
    }

    /**
     * Normalise a key value for a given model to the raw UUID that hits the
     * database (ADR-0004). Bare UUIDs (and any non-string) pass through
     * untouched; a Public ID must carry this model's prefix or it throws.
     *
     * @param  class-string<Model>  $model
     */
    public function normalizeKeyForModel(mixed $value, string $model): mixed
    {
        if (! is_string($value) || ! str_contains($value, '_')) {
            return $value;
        }

        [$prefix, $tail] = $this->parse($value);
        $expected = $this->registry->prefixForModel($model);

        if ($prefix !== $expected) {
            throw PrefixMismatchException::make($expected, $prefix);
        }

        return Codec::decode($tail);
    }
}
