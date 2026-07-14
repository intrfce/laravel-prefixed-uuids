<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

/**
 * Thrown at registry-build time when a prefix, model, or table is registered
 * more than once with conflicting mappings. See ADR-0012.
 */
class DuplicatePrefixException extends PrefixedUuidException
{
    public static function prefix(string $prefix, string $existing, string $incoming): self
    {
        return new self("Prefix [{$prefix}] is already mapped to [{$existing}]; cannot remap to [{$incoming}].");
    }

    public static function model(string $model, string $existing, string $incoming): self
    {
        return new self("Model [{$model}] is already mapped to prefix [{$existing}]; cannot remap to [{$incoming}].");
    }

    public static function table(string $table, string $existing, string $incoming): self
    {
        return new self("Table [{$table}] already backs registered model [{$existing}]; [{$incoming}] conflicts.");
    }
}
