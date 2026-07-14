<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Exceptions\InvalidPublicIdException;
use Intrfce\PrefixedUuids\Exceptions\PrefixMismatchException;

/**
 * Stateless helper composing the codec (ADR-0002) with each model's declared
 * prefix (ADR-0016). Centralises the parse / encode / normalise logic reused by
 * the trait, the custom builder, route binding, and the validation rule. There
 * is no registry: every operation is scoped to a known model, whose prefix is
 * read from its #[PrefixedId] attribute.
 */
class PrefixedIdManager
{
    /** Build a Public ID from a raw UUID and an explicit prefix. */
    public function encode(string $uuid, string $prefix): string
    {
        return $prefix.'_'.Codec::encode($uuid);
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
        $expected = PrefixedId::forModel($model);

        if ($prefix !== $expected) {
            throw PrefixMismatchException::make($expected, $prefix);
        }

        return Codec::decode($tail);
    }
}
