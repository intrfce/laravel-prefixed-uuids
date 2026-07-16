# ADR 0017: Declare the prefix with an abstract `idPrefix()` method, not an attribute

- **Status:** accepted
- **Date:** 2026-07-16
- **Supersedes:** ADR-0016 (prefix via `#[PrefixedId]` attribute), and with it the last live piece of
  ADR-0006. The *no-registry, model-scoped* decision of ADR-0016 stands unchanged — only the
  mechanism for reading a model's prefix changes.

## Context

ADR-0016 moved the prefix onto the model and read it from a `#[PrefixedId('cus')]` attribute via
reflection, cached per class. That worked, but it doesn't match how Laravel's own Eloquent
concerns let a model declare per-model behaviour: they use plain methods you override —
`uniqueIds()`, `newUniqueId()`, `getRouteKeyName()`, `getMorphClass()`. An attribute plus a
reflection read plus a static cache is machinery a method makes unnecessary, and it reads as
foreign to a Laravel developer.

The attribute also deferred a "missing prefix" mistake to runtime: a model using the trait without
the attribute only failed the first time its Public ID was touched (`MissingPrefixException`).

## Decision

Declare the prefix by implementing an **abstract** method on the trait:

```php
class Customer extends Model
{
    use HasPrefixedUuids;

    public function idPrefix(): string
    {
        return 'cus';
    }
}
```

- The method is `abstract` on `HasPrefixedUuids`, so a model that forgets it does not compile. The
  "missing prefix" case is a static error, not a runtime one — `MissingPrefixException` is deleted.
- The prefix is read with `(new $model)->idPrefix()` where only a class-string is on hand (the
  validation rule, the manager). This is only reached on the Public-ID input path; bare-UUID
  queries short-circuit before instantiating, so the hot path is untouched.
- `#[PrefixedId]`, its reflection cache, and `MissingPrefixException` are removed.

## Consequences

- Matches Laravel's override-a-method idiom; no attribute, no reflection, no per-class cache.
- Misconfiguration is caught at compile time by PHP and the IDE, not at runtime.
- One fewer exception class and one fewer source file.
- A model's prefix can now be computed (e.g. per tenant) rather than a compile-time constant — a
  capability the attribute could not offer.
- **Trade-off:** a forgotten prefix is now a fatal "abstract method not implemented" error rather
  than a catchable exception. This is the intended stronger guarantee.

## Alternatives considered

- **Keep the attribute (ADR-0016):** rejected — it doesn't match core idiom and defers the error
  to runtime.
- **A non-abstract method (or `$prefix` property) with a default that throws:** viable and keeps a
  catchable error, but a genuinely-required value with no sensible default is exactly what an
  abstract method expresses; the compile-time guarantee is worth more than a catchable throw.
