# Glossary — Prefixed UUIDs

The ubiquitous language for this package. Every term here has exactly one meaning; if we
catch ourselves using two words for one thing (or one word for two things), we fix it here first.

| Term | Meaning | Status |
|------|---------|--------|
| **UUID** | The internal, canonical 16-byte identifier and primary key. **v7**, stored via `$table->uuid()`. | 🟢 ADR-0005 |
| **Registry / `map()`** | Central, morph-map-style `prefix ↔ model` map, declared via `PrefixedId::map([...])`. Single source of truth for prefixes. | 🟢 ADR-0011 |
| **Global resolver** | `PrefixedId::resolve($publicId) → Model` using the registry. | 🟢 ADR-0011 |
| **`public_id`** | Read accessor returning the prefixed form: `$model->public_id`. | 🟢 ADR-0012 |
| **id mutator** | Setter accepting a bare UUID or correctly-prefixed Public ID; stores the UUID (get stays raw). | 🟢 ADR-0008 |
| **Custom builder** | Package Eloquent builder that decodes Public IDs in `whereKey()`, so `find()`/`destroy()` accept either form. | 🟢 ADR-0014 |
| **`public_id_exists`** | Decode-aware existence rule; string `public_id_exists:{table}` or fluent `PublicIdExists::for()`. Fails soft on bad input. | 🟢 ADR-0015 |
| **Prefix** | Short human-readable token identifying the *type* of a record, e.g. `user`, `cus`. Stripe-style. | 🟢 ADR-0003 |
| **Public ID** | The externally-exposed identifier: `<prefix>_<tail>`. A **reversible codec** over the UUID. | 🟢 ADR-0001 |
| **Tail** | The part after `<prefix>_`: the UUID's 16 raw bytes as **base62** (`[0-9A-Za-z]`), ~22 chars. | 🟢 ADR-0002 |
| **Encode** | Pure function `uuid -> publicId`. | 🟢 ADR-0001 |
| **Decode** | Pure function `publicId -> uuid`, no DB access. | 🟢 ADR-0001 |
| **ORM key** | The value `getKey()` returns; used by FKs, joins, eager loading, `whereKey()`. **Always the raw UUID.** | 🟢 ADR-0004 |
| **Outward surface** | Where the Public ID appears automatically: JSON serialization, route binding, a read accessor. | 🟢 ADR-0004 |
| **Transformer** | Trait-provided get/set logic converting between UUID and Public ID *at the outward surfaces only*. | 🟡 undecided |

Legend: 🟡 undecided · 🟢 decided (see ADR) · 🔴 contested
