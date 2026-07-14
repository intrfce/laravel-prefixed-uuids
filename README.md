# Laravel Prefixed UUIDs

Stripe-style public identifiers for Eloquent models — `user_3kQ4mZp8Vh7kQp2Rt5Nx9`, `cus_0192f8a1...` — that are a **reversible base62 encoding** of the model's **UUID v7** primary key.

```php
$user->id;         // "0192f8a1-9b2c-71d4-a716-446655440000"  (raw UUID — the ORM key)
$user->public_id;  // "user_3kQ4mZp8Vh7kQp2Rt5Nx9"            (what the world sees)

User::find('user_3kQ4mZp8Vh7kQp2Rt5Nx9');   // decodes, then finds
PrefixedId::resolve('cus_0Vh7kQp2Rt5Nx93k'); // -> Customer model
```

Unlike a real Stripe ID (an opaque random token), this ID **decodes back to the UUID with pure math — no database lookup**. That makes it fast and stateless, at the cost of not being opaque (see [Caveats](#caveats)).

## Requirements

- PHP 8.3+
- Laravel 12+

## Installation

```bash
composer require intrfce/laravel-prefixed-uuids
```

The service provider and `PrefixedId` facade are auto-discovered.

## Setup

### 1. Register your prefixes

Declare `prefix => model` mappings once, morph-map style, in a service provider's `boot()`:

```php
use Intrfce\PrefixedUuids\Facades\PrefixedId;

public function boot(): void
{
    PrefixedId::map([
        'user' => \App\Models\User::class,
        'cus'  => \App\Models\Customer::class,
    ]);
}
```

This is the **single source of truth** for prefixes. A model must be registered before its Public ID is used — an unregistered model throws `ModelNotRegisteredException`.

### 2. Add the trait

```php
use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedId;

class User extends Model
{
    use HasPrefixedId;
}
```

The trait sets the key to a non-incrementing string, and auto-populates it with a UUID v7 on create.

### 3. Use a UUID primary key in your migration

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    // ...
});
```

## Usage

### Reading the ID

```php
$user = User::create(['name' => 'Ada']);

$user->id;         // raw UUID — used by relationships, foreign keys, joins
$user->getKey();   // same raw UUID
$user->public_id;  // "user_3kQ4mZp8Vh7kQp2Rt5Nx9"
```

The **raw UUID stays the ORM key** — relationships, eager loading, and foreign keys are never touched, so everything keeps working normally.

### JSON / API output

`toArray()` and `toJson()` present the Public ID as `id` and **hide the raw UUID**:

```php
$user->toArray();
// [ "id" => "user_3kQ4mZp8Vh7kQp2Rt5Nx9", "name" => "Ada", ... ]
```

### Route model binding

URLs use the Public ID automatically, and implicit binding decodes it:

```php
Route::get('/users/{user}', fn (User $user) => $user);

route('users.show', $user);        // /users/user_3kQ4mZp8Vh7kQp2Rt5Nx9
// GET /users/user_3kQ4mZp...       -> resolved
// GET /users/<malformed|wrong>     -> 404 (never a 500)
```

### Querying

`find()`, `findOrFail()`, `findMany()`, `destroy()`, and `whereIn('id', …)` all accept **either** a bare UUID or a Public ID:

```php
User::find('user_3kQ4mZp8Vh7kQp2Rt5Nx9');   // decodes then queries
User::find($rawUuid);                         // still works
User::findMany(['user_3kQ…', 'user_9x…']);
User::destroy('user_3kQ4mZp8Vh7kQp2Rt5Nx9');
User::whereIn('id', [$publicIdA, $publicIdB])->get();

User::find('cus_0Vh7kQp2Rt5Nx93k');           // wrong prefix -> PrefixMismatchException
```

### Assigning the key

```php
$user->id = 'user_3kQ4mZp8Vh7kQp2Rt5Nx9';    // validates prefix, stores the raw UUID
$user->id = $rawUuid;                          // also fine
$user->id = 'cus_...';                         // PrefixMismatchException
```

> Note the deliberate asymmetry: you may *assign* a Public ID, but reading `$user->id` returns the raw UUID. Read `$user->public_id` for the prefixed form.

### The facade

```php
use Intrfce\PrefixedUuids\Facades\PrefixedId;

PrefixedId::encode($uuid, 'user');   // "user_3kQ4mZp8Vh7kQp2Rt5Nx9"
PrefixedId::decode('user_3kQ4mZp…'); // raw UUID (no DB access)
PrefixedId::resolve('cus_0Vh7kQ…');  // Customer model (or null), any registered type
```

## Validation

Laravel's built-in `exists` rule runs a raw query and **cannot** match a Public ID against a UUID column (it silently fails on MySQL and 500s on Postgres). Use the decode-aware rule instead.

**String form** — the target is the table:

```php
$request->validate([
    'customer' => 'public_id_exists:customers',
]);
```

**Fluent form** — for constraints and soft-delete handling:

```php
use Intrfce\PrefixedUuids\Rules\PublicIdExists;

$request->validate([
    'customer' => PublicIdExists::for(Customer::class)
        ->where('active', true)
        ->withoutTrashed(),
]);
```

Bad input (malformed tail, wrong or unknown prefix) fails as a normal validation message — it never throws. A rule targeting an unregistered table is a configuration error and throws `ModelNotRegisteredException`.

## Exceptions

All extend `Intrfce\PrefixedUuids\Exceptions\PrefixedUuidException`:

| Exception | When |
|-----------|------|
| `PrefixMismatchException`     | assigning/querying with another model's prefix |
| `UnknownPrefixException`      | a well-formed prefix that isn't registered |
| `InvalidPublicIdException`    | malformed value or a tail that isn't valid base62 |
| `DuplicatePrefixException`    | a prefix/model/table registered twice with conflict |
| `ModelNotRegisteredException` | a model/table used before being registered |

## How it works

"The id" is two things that this package deliberately keeps separate:

- **ORM key** — the raw UUID v7. What `getKey()`, foreign keys, joins, and eager loading use. Never changed.
- **Public ID** — `<prefix>_<base62(uuid bytes)>`, ~22-char tail, URL-safe. Shown on JSON, URLs, and the `public_id` accessor.

Decoding is pure integer arithmetic over the UUID's 16 raw bytes (no `ext-gmp`/`ext-bcmath` required). The full rationale for every decision lives in [`docs/`](./docs/README.md) as ADRs.

## Caveats

- **Not opaque.** A Public ID reveals its UUID, and because UUID v7 embeds a creation timestamp, it reveals *when the record was created*. If you need unguessable external IDs, this is the wrong tool.
- **Registration is mandatory.** A model must be in `PrefixedId::map([...])` before its Public ID is used.
- **set/get asymmetry** on the key is intentional — assign a Public ID, read back a raw UUID.

## Testing

```bash
composer install
./vendor/bin/pest
```

The suite includes an informational benchmark (never gates the build):

```bash
./vendor/bin/pest --group=benchmark
# [benchmark] encode 1000: ~12 ms | decode 1000: ~12 ms
```

## License

MIT.
