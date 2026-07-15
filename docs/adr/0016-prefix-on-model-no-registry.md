# ADR 0016: Prefix lives on the model; drop the central registry and global resolver

- **Status:** accepted
- **Date:** 2026-07-14
- **Supersedes:** ADR-0011 (central morph-map-style registry)
- **Reinstates:** ADR-0006 (prefix declared via a PHP attribute), with one deliberate difference —
  no global resolver.

## Context

ADR-0011 introduced a central `prefix ↔ model` registry (`PrefixedId::map([...])`) and justified it
by one requirement: the **global resolver**, `PrefixedId::resolve('cus_xxx') -> Customer`, needs an
authoritative `prefix -> model` map available before any model is autoloaded. A per-model attribute
(ADR-0006) can't feed that resolver, because an attribute is only readable once its class is loaded —
so ADR-0011 superseded ADR-0006.

In practice the registry became the one bit of wiring every consumer had to remember, and a second
source of truth to keep in sync with the models themselves. Reassessing the requirement that forced
it: the global resolver (`resolve()`/`decode()` from a bare Public ID) and the table-form validation
rule (`public_id_exists:{table}`) are the *only* operations that need `prefix/table -> model`. Every
other operation already knows its model — `Customer::find('cus_…')`, route binding, `$model->public_id`,
the fluent `PublicIdExists::for(Customer::class)` rule.

## Decision

Declare the prefix on the model with a PHP attribute, and delete the registry:

```php
#[PrefixedId('cus')]
class Customer extends Model { use HasPrefixedUUID; }
```

- The prefix is read from the `#[PrefixedId]` attribute via reflection, cached per class (the prefix
  is immutable per class, so the cache never needs flushing).
- A model using the trait without the attribute throws `MissingPrefixException` the first time its
  Public ID is used.
- **The global resolver is dropped.** There is no `PrefixedId::resolve()` / `decode()` from a bare
  Public ID, and no `PrefixedId` facade. All operations are model-scoped.
- **The string/table validation form is dropped.** `public_id_exists:{table}` needed `table -> model`;
  only the fluent `PublicIdExists::for(Model::class)` survives.

## Consequences

- One source of truth. The prefix lives with the model; nothing to register or keep in sync.
- No boot-time wiring, no filesystem scan, no registry singleton to reset between tests.
- **Lost capability:** resolving/decoding a Public ID when you *don't* already know its model. If a
  consumer needs this later, it can be layered back on with an explicit, opt-in list of participating
  model classes (the package reads each one's prefix) — without reintroducing a mandatory registry.
- `DuplicatePrefixException` and `UnknownPrefixException` disappear: with no central map there is no
  build-time uniqueness check and no "unknown prefix" lookup. Two models sharing a prefix is no
  longer detectable centrally (each still decodes only against its own model).

## Alternatives considered

- **Keep the registry (ADR-0011):** rejected — its sole justification (the global resolver) is a
  capability we chose to drop.
- **Attribute + a registered list of model classes** to preserve `resolve()`: viable, but adds back
  wiring for a capability not currently needed. Left as the documented future path.
- **Property or method instead of an attribute:** the attribute reads as declarative metadata and was
  the preferred style in ADR-0006; kept.
