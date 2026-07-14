<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

/**
 * Thrown when a Public ID's prefix is well-formed but not present in the
 * registry. See ADR-0012.
 */
class UnknownPrefixException extends PrefixedUuidException
{
    public static function make(string $prefix): self
    {
        return new self("No model is registered for prefix [{$prefix}_].");
    }
}
