# Prefixed UUIDs — design docs

`intrfce/laravel-prefixed-uuids` gives Eloquent models a Stripe-style public identifier
(`user_3kQ4mZp...`) that is a **reversible base62 encoding** of the model's UUID v7 primary key.

These docs are the product of a grilling session: every non-obvious choice is an ADR, and the
ubiquitous language lives in the [glossary](./glossary.md).

## The one idea that shapes everything

"The id" was two things wearing one name, and separating them is the whole design:

- **ORM key** — the raw UUID. What `getKey()`, foreign keys, joins, and eager loading use.
  **This package never changes it.** (ADR-0004)
- **Public ID** — the outward face: `<prefix>_<base62(uuid bytes)>`. Appears only on outward
  surfaces (JSON, URLs, an accessor), and always decodes back to the UUID with pure math. (ADR-0001)

Hijacking the `id` accessor would have collapsed these back together and broken relationships and
routing — that was the central thing the grilling killed.

## End-to-end shape

```php
// 1. The model opts in and declares its prefix on the class. ADR-0016 / 0005
#[PrefixedId('user')]
class User extends Model
{
    use HasPrefixedUUID;   // v7 key, incrementing=false, keyType=string. ADR-0005
}

// 2. Internals use the raw UUID — untouched. ADR-0004
$user->getKey();      // "0192f8a1-...-7c" (uuid v7)
$user->posts;         // works: FK query uses the uuid

// 3. Outward surfaces show the Public ID. ADR-0004 / 0007
$user->public_id;              // "user_3kQ4mZp..."          (accessor,  ADR-0012)
$user->toArray()['id'];        // "user_3kQ4mZp..."          (uuid hidden, ADR-0007)
route('users.show', $user);    // /users/user_3kQ4mZp...     (route binding decodes)

// 4. Assign either form; reading id stays raw. ADR-0008
$user->id = 'user_3kQ4mZp...';        // validates prefix, stores the uuid (throws on mismatch)

// 5. Key queries accept either form transparently. ADR-0014
User::find('user_3kQ4mZp...');        // decodes, then queries the uuid key
User::find($uuid);                    // bare uuid still works
User::destroy('user_3kQ4mZp...');     // ditto for destroy/findOrFail/whereKey
User::find('cus_9x...');              // wrong model's prefix -> PrefixMismatchException

// 6. Validation: the built-in exists rule is bypassed by Eloquent; use ours. ADR-0015
$request->validate([
    'seller' => PublicIdExists::for(Customer::class)            // name the model directly
        ->where('active', true)->withoutTrashed(),
]);
// bad tail / wrong prefix -> validation message (never throws)
// target model has no #[PrefixedId] -> MissingPrefixException (programmer error)
```

There is deliberately **no** global `resolve('cus_…') -> Customer` from a bare Public ID: prefixes
live on models, not in a central map, so every operation is model-scoped (ADR-0016).

## Components (ADR-0013)

| Piece | Role |
|-------|------|
| `HasPrefixedUUID` trait | v7 key generation, `public_id` accessor, `id` mutator, JSON swap, route binding, custom builder |
| Custom Eloquent builder | decodes Public IDs in `whereKey()`/`whereKeyNot()` so `find()`/`destroy()` accept either form |
| `#[PrefixedId]` attribute | declares a model's prefix on the class; reads it (cached) via reflection |
| `PrefixedIdManager` | stateless helper: `encode()`, `parse()`, `normalizeKeyForModel()` (internal, model-scoped) |
| `PublicIdExists` rule | decode-aware existence validation (fluent form only) |
| `Codec` | base62 ↔ 16 raw UUID bytes; validates 16-byte length |
| Exceptions | `PrefixMismatch`, `MissingPrefix`, `InvalidPublicId` |

## Decisions (ADRs)

| # | Decision |
|---|----------|
| [0001](./adr/0001-public-id-is-reversible-codec.md) | Public ID is a reversible codec, not an opaque token |
| [0002](./adr/0002-encoding-is-base62.md) | Encode the 16 raw UUID bytes as URL-safe base62 (~22 chars) |
| [0003](./adr/0003-mismatched-prefix-throws.md) | Mismatched prefix on set throws |
| [0004](./adr/0004-orm-key-stays-raw-uuid.md) | ORM key stays the raw UUID; Public ID is an outward skin |
| [0005](./adr/0005-uuid-v7-and-storage.md) | UUID v7, stored via `$table->uuid()`; accepts creation-time leak |
| [0006](./adr/0006-prefix-declared-via-php-attribute.md) | Prefix via PHP attribute (superseded by 0011, reinstated by 0016) |
| [0007](./adr/0007-json-replaces-id-hides-uuid.md) | JSON `id` = Public ID; raw UUID hidden |
| [0008](./adr/0008-decode-paths.md) | Decode paths: `id` mutator + static codec |
| [0009](./adr/0009-benchmark-is-informational.md) | Benchmark reports timings; never gates the build |
| [0010](./adr/0010-test-stack.md) | Pest on Orchestra Testbench |
| [0011](./adr/0011-prefixes-registered-morph-map-style.md) | ~~Prefixes registered centrally, morph-map style~~ (superseded by 0016) |
| [0012](./adr/0012-resolver-error-semantics.md) | Typed bad-input exceptions (registry-uniqueness half superseded by 0016) |
| [0013](./adr/0013-package-identity.md) | Package identity and support matrix (L12+/PHP 8.3+) |
| [0014](./adr/0014-find-accepts-public-id.md) | `find()`/`whereKey()` transparently accept a Public ID |
| [0015](./adr/0015-validation-existence-rule.md) | Decode-aware existence rule (fluent form), fails soft |
| [0016](./adr/0016-prefix-on-model-no-registry.md) | Prefix on the model via attribute; drop the registry + global resolver |

## Known properties & non-goals

- **Not opaque.** A Public ID reveals its UUID, and (via v7) the record's creation time. If you
  need unguessable external IDs, this package is the wrong tool.
- **A prefix is mandatory.** A model using the trait without a `#[PrefixedId]` attribute throws
  `MissingPrefixException` when used (ADR-0016).
- **No global resolver.** There is no `resolve('cus_…') -> Customer` from a bare Public ID; every
  operation is model-scoped (ADR-0016).
- **set/get asymmetry** on `id` is intentional (ADR-0008): you may *assign* a prefixed value, but
  reading `id` returns the raw UUID; read `public_id` for the prefixed form.

## Still open (for implementation)

- Exact base62 algorithm & the leading-zero-byte padding contract (ADR-0012 names the requirement).
- Whether the `id` mutator should also fire for factory/seed assignment paths (likely yes).
- An opt-in list of participating models to restore global `resolve()`, if a use case appears (ADR-0016).
