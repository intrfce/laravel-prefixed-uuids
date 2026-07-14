<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Codec;
use Intrfce\PrefixedUuids\Exceptions\PrefixedUuidException;
use Intrfce\PrefixedUuids\PrefixedId;
use Intrfce\PrefixedUuids\PrefixedIdManager;

/**
 * Decode-aware existence rule (ADR-0015). Laravel's built-in `exists` runs a
 * raw query-builder query and never sees the model layer, so it cannot match a
 * Public ID against a UUID column. This rule decodes first, then checks.
 *
 * Naming the model directly (there is no registry to look one up, ADR-0016):
 *   PublicIdExists::for(Customer::class)->where('active', true)->withoutTrashed()
 *
 * Failure handling is deliberately split (ADR-0015):
 *   - user-input failures (bad tail, wrong prefix) fail soft as a validation
 *     message and never throw;
 *   - a target model with no #[PrefixedId] attribute is a programmer error and
 *     throws MissingPrefixException.
 *
 * @param  class-string<Model>  $model
 */
class PublicIdExists implements ValidationRule
{
    /** @var array<int, array<int, mixed>> */
    private array $wheres = [];

    private bool $withoutTrashed = false;

    public function __construct(private readonly string $model) {}

    /** @param  class-string<Model>  $model */
    public static function for(string $model): self
    {
        return new self($model);
    }

    /** Add a constraint, mirroring query-builder where() (column/value, operator, or closure). */
    public function where(mixed ...$args): self
    {
        $this->wheres[] = $args;

        return $this;
    }

    public function withoutTrashed(): self
    {
        $this->withoutTrashed = true;

        return $this;
    }

    public function withTrashed(): self
    {
        $this->withoutTrashed = false;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $manager = app(PrefixedIdManager::class);

        // Configuration error — loud (throws) if the model has no #[PrefixedId].
        $expectedPrefix = PrefixedId::forModel($this->model);

        // Everything below is untrusted input — fail soft, never throw.
        if (! is_string($value) || ! str_contains($value, '_')) {
            $fail('The selected :attribute is invalid.');

            return;
        }

        try {
            [$prefix, $tail] = $manager->parse($value);

            if ($prefix !== $expectedPrefix) {
                $fail('The selected :attribute is invalid.');

                return;
            }

            $uuid = Codec::decode($tail);
        } catch (PrefixedUuidException) {
            $fail('The selected :attribute is invalid.');

            return;
        }

        if (! $this->existsInDatabase($uuid)) {
            $fail('The selected :attribute is invalid.');
        }
    }

    private function existsInDatabase(string $uuid): bool
    {
        /** @var Model $instance */
        $instance = new $this->model;

        // withoutScopes so trashed rows are included by default (mirrors exists);
        // withoutTrashed() then re-excludes them.
        $query = $instance->newQueryWithoutScopes()->whereKey($uuid);

        foreach ($this->wheres as $args) {
            $query->where(...$args);
        }

        if ($this->withoutTrashed && method_exists($instance, 'getDeletedAtColumn')) {
            $query->whereNull($instance->getDeletedAtColumn());
        }

        return $query->exists();
    }
}
