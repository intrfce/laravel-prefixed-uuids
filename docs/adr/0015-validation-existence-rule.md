# ADR 0015: A decode-aware existence rule, in string and fluent forms

- **Status:** accepted
- **Date:** 2026-07-14

## Context

Laravel's `exists`/`unique` rules are handled by `DatabasePresenceVerifier`, which runs a raw
**query-builder** query (`DB::table(...)->where('id', $value)`) — Eloquent is bypassed, so the
custom builder from ADR-0014 does not apply. Feeding a Public ID to `exists:customers,id` therefore
fails: a silent "invalid" on MySQL, and a `22P02` QueryException (500) on Postgres' native `uuid`
column.

Separately: validation is the trust boundary for untrusted input. Rules must **never throw** on a
bad value — they must return a message. This deliberately inverts the fail-loud stance used
everywhere else (ADR-0003/0012), which is correct: those guard *programmer* errors; this guards
*user input*.

## Decision

Ship a decode-aware existence rule in the two forms Laravel itself uses for `exists`:

- **String:** `'public_id_exists:{table}'` — e.g. `'customer' => 'public_id_exists:customers'`.
- **Fluent object:** `PublicIdExists::for(Customer::class)->where('active', true)->withoutTrashed()`
  — for constraints/soft-delete options a string can't express.

Behaviour:

- The target names the **table** (string) or **model** (object). The rule uses the registry to map
  table/model → prefix + key column.
- It decodes the value to a UUID, then checks existence against the raw key (optionally applying
  `where()` / trashed constraints).
- **Two failure kinds, opposite handling:**
  - *Input* failures — malformed tail, unknown prefix, or a prefix that doesn't match the target
    model — **fail soft** as a validation message ("The selected customer is invalid."). Never
    throws mid-request.
  - *Configuration* failures — target table/model not registered — are a **programmer error** and
    **throw** (`ModelNotRegisteredException`) at rule evaluation, not a silent validation failure.

## Consequences

- `exists`-style checks work with Public IDs; the built-in `exists:...,id` remains a documented
  footgun to avoid.
- Retrieval after validation still uses `Model::find()` (ADR-0014) or `PrefixedId::resolve()`; the
  rule only *checks* — it does not hand back the decoded UUID.
- Not shipped in v1: a format-only (no-DB) rule and a FormRequest auto-decode helper. Revisit on
  demand.
- `unique` is not shipped (IDs are server-generated v7); the same mechanism applies if a use case
  appears.
- The registry must also build a `table -> model` index; a table backing two registered models is a
  configuration error surfaced at build (cf. ADR-0012).

## Alternatives considered

- **String-only with a param grammar for wheres:** can't express closures — rejected.
- **Object-only:** loses the terse string ergonomics the user wanted — rejected.
- **Make `exists` itself work:** not feasible without replacing the presence verifier globally —
  rejected as too invasive.
