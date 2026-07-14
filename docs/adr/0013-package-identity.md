# ADR 0013: Package identity and support matrix

- **Status:** accepted
- **Date:** 2026-07-14

## Decision

- **Composer package:** `intrfce/laravel-prefixed-uuids`
- **Namespace root:** `Intrfce\PrefixedUuids`
- **Public surface:**
  - Trait `HasPrefixedId`
  - Facade `PrefixedId` (→ `map()`, `resolve()`, `encode()`, `decode()`)
  - `Codec` (base62 ↔ 16 raw bytes)
  - `PrefixIdRegistry` (prefix ↔ model)
  - Base exception `PrefixedUuidException`, with `PrefixMismatchException`,
    `UnknownPrefixException`, `InvalidPublicIdException`, `DuplicatePrefixException`,
    `ModelNotRegisteredException`
- **Support matrix:** `laravel/framework: ^12 | ^13`, `php: ^8.3`.

## Consequences

- CI tests the L12 and L13 lines on PHP 8.3+.
- `Str::uuid7()` is available across the whole matrix (L11+), so v7 generation needs no extra dep.
- Drops Laravel 11 users; acceptable for a fresh package.
