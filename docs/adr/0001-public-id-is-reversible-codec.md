# ADR 0001: The Public ID is a reversible codec, not an opaque token

- **Status:** accepted
- **Date:** 2026-07-14

## Context

Stripe-style IDs come in two incompatible flavours. A *true* Stripe ID (`cus_NffrFeUfNV2Hib`)
is a random base62 token stored in its own column and resolved by DB lookup — it encodes
nothing. The alternative is a *reversible codec*: the Public ID is a lossless encoding of the
underlying UUID, so it can be decoded back to the UUID with pure computation and no query.

The plan calls for "encode" and "decode" operations and a benchmark that times encoding/decoding
1000 IDs. That vocabulary only exists in the codec model.

## Decision

The Public ID is a **reversible codec** over the record's UUID. `decode(publicId) -> uuid` is a
pure function requiring no database access. `encode(uuid) -> publicId` is its inverse.

## Consequences

- Encoding/decoding is O(1) and benchmarkable in isolation — no DB fixture needed.
- **No opacity:** anyone holding a Public ID can recover the raw UUID. We accept this. If we
  ever need unguessable external IDs, that is a *different* feature (a stored random token) and a
  future ADR.
- No extra column and no lookup index are required for the encoding itself.

## Alternatives considered

- **Opaque stored token (true Stripe):** rejected — contradicts the encode/decode framing and the
  benchmark, and adds a column + index we don't need for the stated goal.
