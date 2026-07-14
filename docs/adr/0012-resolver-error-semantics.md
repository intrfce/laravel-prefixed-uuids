# ADR 0012: Registry uniqueness and bad-input error semantics

- **Status:** partially superseded by ADR-0016
- **Date:** 2026-07-14
- **Amended:** 2026-07-14 — ADR-0016 removed the registry, so the *uniqueness* rules here
  (`DuplicatePrefixException`) and the *unknown-prefix* case (`UnknownPrefixException`, thrown by the
  now-removed `resolve()`/`decode()`) no longer apply. The bad-input semantics that remain in force —
  `InvalidPublicIdException` (malformed tail / wrong byte length) and `PrefixMismatchException` (wrong
  prefix for a known model), plus the 16-byte length contract on the decoder — are unchanged.

## Context

Given a central registry (ADR-0011) and public methods that turn strings back into UUIDs/models,
we must define what happens on conflicting registration and on malformed/unknown input. The
project's stance elsewhere is "fail loud" (ADR-0003).

## Decision

- **Uniqueness — fail loud at registry build.** Registering the same prefix for two models, or the
  same model under two prefixes, throws `DuplicatePrefixException` at registration time (when
  `map()` runs / the maps merge), not lazily at resolve time.
- **Unknown prefix** (well-formed but not in the registry): `resolve()`/`decode()` throw
  `UnknownPrefixException`.
- **Malformed tail** (not valid base62 / wrong decoded byte length): throw
  `InvalidPublicIdException`.
- **Wrong prefix on assignment** to a known model: `PrefixMismatchException` (ADR-0003).

All are typed exceptions extending a common package base exception.

## Consequences

- Every failure mode is a distinct, catchable type — consumers can distinguish "you passed
  garbage" from "that prefix isn't registered" from "wrong model."
- No nullable return plumbing; callers who want soft behaviour wrap in try/catch.
- The base62 decoder must validate length: 16 raw bytes exactly, left-padding shorter results and
  rejecting longer ones (leading-zero-byte UUIDs encode to a shorter tail).

## Alternatives considered

- **Return null on bad input:** rejected — inconsistent with the fail-loud stance; spreads null
  checks through calling code.
- **Detect duplicates only at resolve:** rejected — hides a config error until runtime.
