# ADR 0011: Prefixes are registered centrally, morph-map style

- **Status:** superseded by ADR-0016
- **Date:** 2026-07-14
- **Supersedes:** ADR-0006 (per-class PHP attribute)
- **Superseded:** 2026-07-14 — the central registry existed only to feed the global resolver; once
  that capability was dropped (ADR-0016), the prefix moved back onto the model as an attribute.

## Context

The global resolver (`PrefixedId::resolve('cus_xxx') -> Customer`) needs an authoritative
`prefix -> model` map covering every participating model, available before any model is
necessarily autoloaded. A per-class attribute (ADR-0006) can't provide that without scanning the
filesystem. Laravel already solves the identical problem for polymorphic relations with
`Relation::enforceMorphMap([...])` — one explicit static registration, read from anywhere.

## Decision

Prefixes are declared with a single central registration call, mirroring the morph map, typically
in a service provider's `boot()`:

```php
PrefixedId::map([
    'user' => User::class,
    'cus'  => Customer::class,
]);
```

- The registry is the **single source of truth** for `prefix <-> model`. There is **no**
  `#[PrefixedId]` attribute and no per-model prefix property.
- The `HasPrefixedUUID` trait resolves its own prefix by looking `static::class` up in the registry.
- The map is bidirectional: prefix→class (for `resolve()`) and class→prefix (for the trait).

## Consequences

- Reliable global resolution without model enumeration or filesystem scanning.
- A model used before its prefix is registered is an error: the trait throws
  `ModelNotRegisteredException` (analogous to using an unmapped morph type).
- Registration is a required setup step, documented prominently — this is the one bit of wiring a
  consumer must not forget.
- A published config file is **not** the primary mechanism for v1; the static `map()` call is.
  (Config-driven registration may be layered on later without changing the resolver.)

## Alternatives considered

- **PHP attribute (ADR-0006):** can't feed a global resolver without scanning — superseded.
- **Directory auto-scan:** brittle, boot cost — rejected.
- **Lazy self-registration:** `resolve()` misses unloaded models — rejected.
