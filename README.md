> Note: This is not production ready, and the docs aren't ready yet.

# Laravel Prefixed UUIDs

Stripe-style public identifiers for Eloquent models — `user_3kQ4mZp8Vh7kQp2Rt5Nx9`, `cus_0192f8a1...` — that are a **reversible base62 encoding** of the model's **UUID v7** primary key.

```php
$user->id;         // "0192f8a1-9b2c-71d4-a716-446655440000"  (raw UUID — the ORM key)
$user->public_id;  // "user_3kQ4mZp8Vh7kQp2Rt5Nx9"            (what the world sees)

User::find('user_3kQ4mZp8Vh7kQp2Rt5Nx9');   // decodes, then finds
route('users.show', $user);                  // /users/user_3kQ4mZp8Vh7kQp2Rt5Nx9
```

Unlike a real Stripe ID (an opaque random token), this ID **decodes back to the UUID with pure math — no database lookup**. That makes it fast and stateless, at the cost of not being opaque (see [Caveats](#caveats)).

## Requirements

- PHP 8.3+
- Laravel 12+

## Installation

```bash
composer require intrfce/laravel-prefixed-uuids
```

The service provider is auto-discovered.

## Setup

### 1. Add the trait and declare a prefix

Each model owns its prefix, returned from an `idPrefix()` method on the class — there is no central registry to keep in sync:

```php
use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUuids;

class User extends Model
{
    use HasPrefixedUuids;

    public function idPrefix(): string
    {
        return 'user';
    }
}
```

The trait builds on Laravel's own `HasUuids`, so the key is a non-incrementing string auto-populated with a UUID v7 on create (override `newUniqueId()` to change that). `idPrefix()` is `abstract` — a model that uses the trait without implementing it won't compile, so there is no runtime "missing prefix" state to guard against.

### 2. Use a UUID primary key in your migration

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

## Validation

Laravel's built-in `exists` rule runs a raw query and **cannot** match a Public ID against a UUID column (it silently fails on MySQL and 500s on Postgres). Use the decode-aware rule instead, naming the model directly:

```php
use Intrfce\PrefixedUuids\Rules\PublicIdExists;

$request->validate([
    'customer' => PublicIdExists::for(Customer::class)
        ->where('active', true)
        ->withoutTrashed(),
]);
```

Bad input (malformed tail, wrong prefix) fails as a normal validation message — it never throws. The target model must use `HasPrefixedUuids`; its prefix is read from `idPrefix()`.

## Exceptions

All extend `Intrfce\PrefixedUuids\Exceptions\PrefixedUuidException`:

| Exception | When |
|-----------|------|
| `PrefixMismatchException`   | assigning/querying with another model's prefix |
| `InvalidPublicIdException`  | malformed value or a tail that isn't valid base62 |

## How it works

"The id" is two things that this package deliberately keeps separate:

- **ORM key** — the raw UUID v7. What `getKey()`, foreign keys, joins, and eager loading use. Never changed.
- **Public ID** — `<prefix>_<base62(uuid bytes)>`, ~22-char tail, URL-safe. Shown on JSON, URLs, and the `public_id` accessor.

Decoding is pure integer arithmetic over the UUID's 16 raw bytes (no `ext-gmp`/`ext-bcmath` required). The full rationale for every decision lives in [`docs/`](./docs/README.md) as ADRs.

## Caveats

- **Not opaque.** A Public ID reveals its UUID, and because UUID v7 embeds a creation timestamp, it reveals *when the record was created*. If you need unguessable external IDs, this is the wrong tool.
- **A prefix is mandatory.** A model using the trait must implement `idPrefix()` — the method is abstract, so this is enforced at compile time, not at runtime.
- **No global resolver.** Because prefixes live on models (not in a central map), there is no `resolve('cus_…') -> Customer` lookup from a bare Public ID. Operations are model-scoped: `Customer::find('cus_…')`, route binding, `$model->public_id`.
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
