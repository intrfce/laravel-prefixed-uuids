# ADR 0010: Test stack is Pest on Orchestra Testbench

- **Status:** accepted
- **Date:** 2026-07-14

## Decision

Tests use **Pest** running on **Orchestra Testbench** (the standard harness for booting a minimal
Laravel app inside a package test suite).

## Consequences

- Testbench provides a real Eloquent/migration environment so relationship, route-binding, and
  serialization behaviour can be tested for real, not mocked.
- The benchmark (ADR-0009) is expressed as a Pest test that prints timings.
- Contributors need PHP + Composer; CI runs against the Laravel/PHP support matrix (to be fixed).
