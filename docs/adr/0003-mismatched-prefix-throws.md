# ADR 0003: Setting a value with a mismatched prefix throws

- **Status:** accepted
- **Date:** 2026-07-14

## Context

The set-transformer receives values that may be: a bare UUID, a correctly-prefixed Public ID, or
a Public ID carrying the *wrong* prefix (e.g. assigning `cus_...` to a `User`). The last case is
almost always a programming error.

## Decision

A mismatched prefix throws a dedicated `PrefixMismatchException`. Fail loud, fail early.

## Consequences

- Cross-type assignment bugs surface immediately instead of corrupting data silently.
- The setter must still cleanly accept the two legitimate inputs (bare UUID, correctly-prefixed
  Public ID) — see open question on whether a bare UUID is allowed on set.
- Tests must cover all three input shapes.

## Alternatives considered

- **Reject silently:** rejected — hides bugs.
- **Strip & accept any prefix:** rejected — makes cross-type assignment undetectable, defeating the
  point of typed prefixes.
