# ADR 0014: `find()` and key queries transparently accept a Public ID

- **Status:** accepted
- **Date:** 2026-07-14

## Context

`Model::find($id)` compiles to `->whereKey($id)->first()`, i.e. `where id = $id` against the raw
UUID column. It does **not** pass through the `id` mutator (ADR-0008) — mutators/casts don't apply
to query bindings. So `Model::find('cus_92839...')` under the base design:

- **MySQL/SQLite** (`char/varchar`): matches nothing → returns `null` (silent footgun).
- **Postgres** (native `uuid`): casting the prefixed string to `uuid` throws `SQLSTATE 22P02`.

`find()` is a natural reach for a caller holding a Public ID, so both failure modes are bad.

## Decision

The trait installs a **custom Eloquent builder** (via `newEloquentBuilder()`) that normalizes
Public IDs in `whereKey()` / `whereKeyNot()` and in `whereIn()` / `whereNotIn()` **when the column
is the key**. This transparently covers `find`, `findOrFail`, `findMany`, `destroy`, and direct
`whereKey`/`whereIn('id', …)` use.

> **Implementation note (discovered while building):** `Model::destroy($ids)` does **not** use
> `whereKey()` — it runs `whereIn($keyName, $ids)`. So the `whereKey` override alone did *not* cover
> `destroy()`. The builder therefore also overrides `whereIn`/`whereNotIn`, gated on the column
> being the key so a legitimate `_` in another column's value is never treated as a Public ID. This
> additionally makes `Model::whereIn('id', [$publicIds])` work.

Normalization rule, using the model's registered prefix `P`:

- Value contains no `_` → treated as a raw UUID, passed through unchanged (covers all internal
  `getKey()`-driven queries and relationships).
- Value starts with `P_` → the tail is decoded to a UUID (malformed tail →
  `InvalidPublicIdException`).
- Value contains `_` but does **not** start with `P_` → it's another model's Public ID →
  `PrefixMismatchException` (ADR-0003 stance).
- Arrays/`Arrayable` (e.g. `find([...])`, `whereKey([...])`) normalize element-wise.

## Consequences

- `Model::find('cus_...')`, `findOrFail`, `destroy`, and `whereKey` "just work" with either form.
- **Invariant preserved:** the value that reaches the database is *always* a raw UUID. This does
  not violate ADR-0004 — `getKey()` output is unchanged; we only normalize query *input*.
- Detection is unambiguous because a UUID string never contains `_`, so `_` presence always signals
  an intended Public ID.
- Small maintenance surface: `whereKey`/`whereKeyNot` signatures must track Laravel across the
  supported matrix (ADR-0013).
- Decode logic is centralized in the builder and reused by route binding.

## Alternatives considered

- **Explicit only (`PrefixedId::resolve()`):** rejected — leaves `find('cus_...')` a driver-
  dependent footgun.
- **Return null on wrong prefix:** rejected — hides cross-type bugs; inconsistent with fail-loud.
