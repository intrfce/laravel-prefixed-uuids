<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Intrfce\PrefixedUuids\Eloquent\PrefixedUuidBuilder;
use Intrfce\PrefixedUuids\Exceptions\PrefixedUuidException;
use Intrfce\PrefixedUuids\PrefixedIdManager;

/**
 * Gives an Eloquent model a Stripe-style prefixed Public ID over a UUID v7
 * primary key. The raw UUID remains the ORM key throughout (ADR-0004); the
 * Public ID appears only on outward surfaces:
 *
 *  - $model->public_id       -> "user_3kQ4mZp..."           (ADR-0004 / 0012)
 *  - $model->toArray()['id'] -> Public ID, raw UUID hidden  (ADR-0007)
 *  - route('users.show', $m) -> Public ID; binding decodes  (ADR-0004)
 *  - $model->id = 'user_..'  -> validates + stores the UUID (ADR-0008)
 *  - User::find('user_..')   -> decodes then queries        (ADR-0014)
 *
 * Key generation and the string, non-incrementing key type come from Laravel's
 * own HasUuids trait, whose newUniqueId() already mints a UUID v7 (ADR-0005);
 * override it on the model to change that.
 *
 * Each model declares its prefix by implementing idPrefix() (ADR-0017). The
 * method is abstract, so a model that forgets it will not compile — there is no
 * runtime misconfiguration state to guard against.
 */
trait HasPrefixedUuids
{
    use HasUuids;

    /** The Public ID prefix for this model, e.g. "cus" -> "cus_3kQ4mZp...". */
    abstract public function idPrefix(): string;

    protected static function prefixedIdManager(): PrefixedIdManager
    {
        return app(PrefixedIdManager::class);
    }

    /** The prefixed Public ID, or null before the key exists. */
    public function toPublicId(): ?string
    {
        $key = $this->getKey();

        if ($key === null || $key === '') {
            return null;
        }

        return static::prefixedIdManager()->encode((string) $key, $this->idPrefix());
    }

    /** Read accessor: $model->public_id (ADR-0012). */
    protected function publicId(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->toPublicId());
    }

    /**
     * Key mutator (ADR-0008): accept a bare UUID or a correctly-prefixed Public
     * ID (wrong prefix throws), always storing the raw UUID. Hydration uses
     * setRawAttributes() and bypasses this, so stored UUIDs are never re-decoded.
     */
    public function setAttribute($key, $value)
    {
        if ($key === $this->getKeyName() && is_string($value) && str_contains($value, '_')) {
            $value = static::prefixedIdManager()->normalizeKeyForModel($value, static::class);
        }

        return parent::setAttribute($key, $value);
    }

    /** Present the Public ID in place of the raw UUID in array/JSON output (ADR-0007). */
    public function attributesToArray()
    {
        $array = parent::attributesToArray();
        $keyName = $this->getKeyName();

        if (array_key_exists($keyName, $array) && $this->getKey() !== null) {
            $array[$keyName] = $this->toPublicId();
        }

        return $array;
    }

    /** URLs carry the Public ID (ADR-0004). */
    public function getRouteKey()
    {
        return $this->toPublicId();
    }

    /**
     * Resolve route bindings by decoding the Public ID to the UUID key. A
     * malformed or wrong-prefix value yields null (a 404), not an exception —
     * routing is a soft boundary.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null && $field !== $this->getKeyName()) {
            return parent::resolveRouteBinding($value, $field);
        }

        try {
            $uuid = static::prefixedIdManager()->normalizeKeyForModel($value, static::class);
        } catch (PrefixedUuidException) {
            return null;
        }

        return $this->newQuery()->whereKey($uuid)->first();
    }

    /** Install the decode-aware key-query builder (ADR-0014). */
    public function newEloquentBuilder($query): Builder
    {
        return new PrefixedUuidBuilder($query);
    }
}
