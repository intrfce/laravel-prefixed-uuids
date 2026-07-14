# ADR 0002: Encode the UUID tail as URL-safe base62

- **Status:** accepted
- **Date:** 2026-07-14

## Context

The tail of the Public ID (`<prefix>_<tail>`) must be short, URL-safe, and visually close to
Stripe. Candidates: standard base64 (has `+ / =`, not URL-safe, ugly), base64url, Crockford
base32 (case-insensitive, longer), raw hex (longest), and base62 (`[0-9A-Za-z]`).

## Decision

Encode the UUID's **16 raw bytes** as **base62** using the alphabet `[0-9A-Za-z]`. This yields a
~22-character tail with no separator-hostile or non-URL-safe characters.

## Consequences

- Tail is ~22 chars vs 32 for hex vs 48 for base64-of-string. Compact and URL-safe.
- Encoding operates on the 16 raw bytes, **not** the 36-char string form — this is the shortest
  input and must be the contract the codec is written against.
- base62 has no fixed-width byte alignment, so the codec is bignum-style (treat 16 bytes as a
  128-bit integer, repeatedly divmod 62). Performance of this is exactly what the benchmark exists
  to measure.

## Alternatives considered

- **base64 (original plan):** rejected — `+ / =` are not URL-safe and look nothing like Stripe.
- **base64url:** viable but keeps `-` and `_` in the tail, muddying the single `_` separator.
- **Crockford base32:** better for humans reading aloud, but longer; revisit if that use case appears.
