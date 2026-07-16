# ADR 0018: Build on Laravel's `HasUuids`; use the modern `Attribute` accessor

- **Status:** accepted
- **Date:** 2026-07-16
- **Refines:** ADR-0005 (UUID v7 key generation) and ADR-0012 (the `public_id` accessor). The
  behaviour is unchanged; the implementation now delegates to Laravel core instead of duplicating
  it.

## Context

The trait hand-rolled what Laravel already ships:

- `initializeHasPrefixedUUID()` set `incrementing = false` and `keyType = 'string'`;
- `bootHasPrefixedUUID()` registered a `creating` hook that filled the key with `Str::uuid7()`;
- `getPublicIdAttribute()` used the pre-Laravel-9 magic-accessor style.

Laravel 12's `HasUuids` (via `HasUniqueStringIds`) does the first two for us: its `newUniqueId()`
already returns a UUID v7, `getKeyType()`/`getIncrementing()` report string/non-incrementing for
the key, and `Model::performInsert()` populates the key when `usesUniqueIds()` is set. Maintaining
a parallel copy only risks drifting from core's edge-case handling.

## Decision

- `HasPrefixedUuids` `use`s `Illuminate\Database\Eloquent\Concerns\HasUuids` internally. The custom
  `initialize`/`boot` hooks are deleted; key generation, key type, and the `newUniqueId()` override
  point now come from core. Users compose one trait, not two.
- The `public_id` accessor is expressed with the modern casts API —
  `protected function publicId(): Attribute` — instead of `getPublicIdAttribute()`. The plain
  helper is renamed `toPublicId()` to free the `publicId` name for the accessor.

## Consequences

- Less code to own; automatic parity with core's UUID key handling; users get the familiar
  `newUniqueId()` hook to swap the UUID version.
- Nesting the trait means core's `HasUniqueStringIds::resolveRouteBindingQuery()` — which 404s on a
  non-UUID value — is inherited. It is *not* hit for the primary implicit binding, because the trait
  overrides the higher-level `resolveRouteBinding()` and never calls the query variant there.
- **Known gap:** scoped/child bindings (`/users/{user}/posts/{post}`) go through
  `resolveRouteBindingQuery()` and do not decode a Public ID. This already didn't work before this
  change (the raw string simply didn't match), so it is not a regression; decoding it is a possible
  follow-up (override `resolveRouteBindingQuery()`/`resolveChildRouteBindingQuery()`).

## Alternatives considered

- **Ask users to add `use HasUuids` themselves alongside our trait:** rejected — worse DX, and it
  exposes trait-ordering and method-conflict subtleties the package should absorb.
- **Keep the hand-rolled boot/initialize:** rejected — duplicates core for no benefit.
