<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

/**
 * Thrown when a Public ID carries a prefix that does not belong to the model it
 * is being used with (e.g. assigning `cus_...` to a User). See ADR-0003 / 0014.
 */
class PrefixMismatchException extends PrefixedUuidException
{
    public static function make(string $expected, string $actual): self
    {
        return new self("Expected a Public ID with prefix [{$expected}_], got prefix [{$actual}_].");
    }
}
