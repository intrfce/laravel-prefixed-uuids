# ADR 0009: The encode/decode benchmark is informational, not a gate

- **Status:** accepted
- **Date:** 2026-07-14

## Context

The suite must time encoding/decoding 1000 IDs. A hard threshold assertion would guard regressions
but is flaky across machines and CI runners.

## Decision

The benchmark **times 1000 encodes and 1000 decodes and reports** the durations (total + per-op).
It never fails the build.

## Consequences

- Stable across environments; no false red builds.
- No automatic regression protection — a future opt-in strict mode (env-gated assertion) can be
  added if drift becomes a problem.
- Output must be visible when run (printed / logged), since its whole value is the number.

## Alternatives considered

- **Hard budget:** flaky across hardware — rejected for v1.
- **Both (report + opt-in assert):** reasonable future step; deferred.
