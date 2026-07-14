# ADR 0007: JSON replaces `id` with the Public ID and hides the raw UUID

- **Status:** accepted
- **Date:** 2026-07-14

## Context

Serialization should present the Public ID (ADR-0004). The question was whether to replace `id`,
add a second field, or use a custom key.

## Decision

In `toArray()`/`toJson()`, the `id` field carries the **prefixed Public ID**, and the raw UUID
does **not** appear in JSON output at all.

## Consequences

- The external contract is clean: consumers only ever see `user_...`; the raw UUID is an internal
  detail.
- The trait must intercept array/JSON serialization to swap the value and remove the raw key —
  without disturbing the in-memory attribute (which stays the UUID for the ORM, per ADR-0004).
- Anything that must surface the raw UUID externally now has to opt in explicitly (unusual).

## Alternatives considered

- **Add `public_id` alongside raw `id`:** leaks the UUID into every response — rejected.
- **Custom key for prefixed form:** keeps raw `id` visible — rejected for the same reason.
