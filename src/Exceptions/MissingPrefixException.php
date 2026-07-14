<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

/**
 * Thrown when a model using HasPrefixedId has no #[PrefixedId] attribute
 * declaring its prefix. This is a configuration / programmer error, not a
 * user-input error. See ADR-0016.
 */
class MissingPrefixException extends PrefixedUuidException
{
    public static function forModel(string $model): self
    {
        return new self("Model [{$model}] has no #[PrefixedId] attribute. Add #[PrefixedId('your_prefix')] to the class.");
    }
}
