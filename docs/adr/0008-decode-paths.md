# ADR 0008: Decoding paths — id mutator + static codec foundation

- **Status:** accepted
- **Date:** 2026-07-14

## Context

Under ADR-0004 the `id` *getter* stays raw (UUID). We still need write/decode paths that
understand a prefixed Public ID. Route binding already decodes (ADR-0004). Beyond that the user
chose a single model-level path: the `id` mutator.

## Decision

- **`id` mutator:** assigning to the key accepts either a bare UUID or a correctly-prefixed Public
  ID. A prefixed value is validated (wrong prefix throws, ADR-0003) and decoded; the raw UUID is
  what gets stored.
- **Static codec** (`PrefixedId::encode()/decode()`) exists as the internal foundation used by the
  mutator, route binding, serialization, and the global resolver (ADR-forthcoming). It is not
  positioned as the primary model-level API but is public and usable.
- No `findByPublicId()` / `whereByPublicId()` model sugar in v1 — the global resolver covers
  lookup.

## Consequences

- **Accepted asymmetry:** `$m->id = 'user_xxx'` stores a UUID, but `$m->id` reads back the raw
  UUID, not `'user_xxx'`. Documented as intentional.
- Hydration from the DB uses `setRawAttributes()` and bypasses the mutator, so stored UUIDs are
  never re-run through decode — correct.
- The mutator must also accept a bare UUID (that is how application code and factories assign keys).

## Alternatives considered

- **Static finder sugar:** not chosen; the global resolver subsumes it.
- **Static-codec-only (no mutator):** rejected — user wants the original set()-transformer behaviour.
