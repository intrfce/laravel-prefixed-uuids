# ADR 0006: Prefix is declared with a PHP 8 attribute on the model class

- **Status:** superseded by ADR-0011
- **Date:** 2026-07-14
- **Superseded:** 2026-07-14 — a central morph-map-style registry replaced the per-class attribute,
  because the global resolver (ADR-0011) needs one authoritative prefix↔model map anyway.

## Context

Each model needs to declare its prefix (`user`, `cus`, ...). Options were a PHP 8 attribute, a
protected property, or a method. The user wants declarative class metadata.

## Decision

Declare the prefix with a PHP 8 attribute on the model class, e.g. `#[PrefixedId('user')]`.

## Consequences

- Reads as metadata, not behaviour — clear intent.
- Requires reflection to read. Reflection per model instance is wasteful, so the resolved prefix
  **must be cached per class** (static map keyed by class name) inside the trait.
- The prefix is static per class — dynamic/per-instance prefixes are intentionally unsupported.
- The trait must fail clearly if a model uses it without the attribute present.

## Alternatives considered

- **Protected property (`$idPrefix`):** more idiomatic Laravel, no reflection — rejected in favour
  of the declarative attribute the user preferred.
- **Method:** allows dynamic prefixes we explicitly don't want.
