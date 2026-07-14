# ADR 0005: UUID v7, stored via Laravel's `uuid()` column

- **Status:** accepted
- **Date:** 2026-07-14

## Context

The UUID is the primary key, so index locality on insert matters. v4 is random (page splits at
scale); v7 is time-ordered (sequential appends). v7 embeds a millisecond creation timestamp in its
first 48 bits — and because the Public ID is a **reversible** codec (ADR-0001), that timestamp is
publicly decodable from any Public ID.

For physical storage, the user opted to let Laravel decide: use `$table->uuid('id')`, whatever that
backs to per driver (char(36) on MySQL, native `uuid` on Postgres, varchar on SQLite).

## Decision

- Generate **UUID v7**. The trait auto-populates the key on `creating` (à la `HasUuids`), sets
  `incrementing = false` and `keyType = 'string'`.
- Store using Laravel's `$table->uuid()` column; do not hand-roll a binary(16) column for v1.
- **Accept** that record creation time leaks through the Public ID.

## Consequences

- Fast, append-friendly primary index.
- The creation-time leak is now a documented property of every Public ID, not an accident. If a
  model must NOT leak timing, it should not use this package (or a future v4 opt-in is needed).
- Codec still operates on the 16 raw bytes; storage form is irrelevant to encoding.

## Alternatives considered

- **v4:** no leak, worse index locality — rejected for the common case; may return as opt-in.
- **binary(16):** smaller/faster index but unreadable in DB tooling and more cast handling —
  deferred; `uuid()` is simpler for v1.
- **Configurable version:** more surface to test — deferred until asked for.
