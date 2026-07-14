<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Exceptions;

/**
 * Thrown when a model (or the table a validation rule targets) is used before
 * it has been registered via PrefixedId::map(). This is a configuration /
 * programmer error, not a user-input error. See ADR-0011 / 0015.
 */
class ModelNotRegisteredException extends PrefixedUuidException
{
    public static function model(string $model): self
    {
        return new self("Model [{$model}] is not registered. Add it to PrefixedId::map([...]).");
    }

    public static function table(string $table): self
    {
        return new self("No registered model backs table [{$table}]. Add its model to PrefixedId::map([...]).");
    }
}
