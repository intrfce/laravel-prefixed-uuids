<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

/**
 * Thrown when a Public ID is structurally malformed: no separator, or a tail
 * that is not valid base62 / does not decode to 16 bytes. See ADR-0012.
 */
class InvalidPublicIdException extends PrefixedUuidException
{
    public static function noSeparator(string $value): self
    {
        return new self("Value [{$value}] is not a Public ID: missing the '_' separator.");
    }

    public static function badTail(string $tail): self
    {
        return new self("Public ID tail [{$tail}] is not valid base62 or does not decode to a 16-byte UUID.");
    }
}
