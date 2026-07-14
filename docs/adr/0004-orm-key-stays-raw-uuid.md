# ADR 0004: The ORM key stays the raw UUID; the Public ID is an outward skin

- **Status:** accepted
- **Date:** 2026-07-14

## Context

"The id should be prefixed" conflated two distinct concepts:

1. **ORM key** — the value `getKey()` returns, used for foreign keys, joins, eager-load matching,
   `whereKey()`, and route resolution.
2. **Outward identifier** — what the API, JSON, and URLs display.

Overriding the `id` *accessor* changes both at once, because `getKey()` reads through the
accessor. That breaks relationships (FK queries use the prefixed string against UUID columns),
foreign-key writes, and route-model binding. Making it work would require overriding `getKey()`,
`getKeyForSaveQuery()`, every relationship's local-key resolution, and `resolveRouteBinding()`.

## Decision

The **ORM key remains the raw UUID, untouched.** The Public ID is presented only on outward
surfaces:

- **JSON serialization** — `toArray()`/`toJson()` present the prefixed Public ID.
- **Route model binding** — URLs emit the Public ID and `resolveRouteBinding()` decodes it.
- **Read accessor** — an explicit accessor returns the prefixed form on demand.

`$model->getKey()`, relationships, and FK writes all continue to use the raw UUID and are never
touched by this package.

## Consequences

- Relationships, eager loading, and FK integrity keep working with zero overrides.
- We do **not** override the `id` accessor's *getter*. (A *setter*/mutator that decodes prefixed
  input back to a UUID is still fine — it only affects writes; see open question.)
- Two representations of one identity coexist. The glossary must keep them distinct: **ORM key**
  (uuid) vs **Public ID** (prefixed). Docs and tests must never blur them.

## Alternatives considered

- **Override the `id` accessor (original plan):** rejected — breaks relationships and routing; see
  Context. The failure table lived in the round-2 grilling notes.
- **Fully separate `public_id` only, `id` never involved in routing/JSON:** viable and the most
  explicit, but the user wants the prefixed form to appear automatically on the standard surfaces.
