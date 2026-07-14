<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Intrfce\PrefixedUuids\PrefixedIdManager;

/**
 * Eloquent builder that decodes Public IDs in key queries (ADR-0014), so
 * find(), findOrFail(), findMany(), destroy(), and direct whereKey() calls
 * accept either a bare UUID or a prefixed Public ID. Bare UUIDs — including
 * every internal getKey()-driven relationship query — pass straight through,
 * so the value reaching the database is always a raw UUID (ADR-0004).
 */
class PrefixedUuidBuilder extends Builder
{
    /** @param  mixed  $id */
    public function whereKey($id)
    {
        return parent::whereKey($this->normalizeKey($id));
    }

    /** @param  mixed  $id */
    public function whereKeyNot($id)
    {
        return parent::whereKeyNot($this->normalizeKey($id));
    }

    /**
     * Normalise Public IDs in whereIn() when the column is the key. This is what
     * makes Model::destroy($publicId) work — destroy() uses whereIn($key, $ids),
     * not whereKey() (ADR-0014). Non-key columns are left untouched so a legit
     * '_' in some other column's value is never mistaken for a Public ID.
     *
     * @param  mixed  $column
     * @param  mixed  $values
     */
    public function whereIn($column, $values = [], $boolean = 'and', $not = false)
    {
        if ($this->isKeyColumn($column)) {
            $values = $this->normalizeKey($values);
        }

        $this->query->whereIn($column, $values, $boolean, $not);

        return $this;
    }

    /**
     * @param  mixed  $column
     * @param  mixed  $values
     */
    public function whereNotIn($column, $values = [], $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /** @param  mixed  $column */
    protected function isKeyColumn($column): bool
    {
        return $column === $this->model->getKeyName()
            || $column === $this->model->getQualifiedKeyName();
    }

    /** @param  mixed  $id */
    protected function normalizeKey($id): mixed
    {
        $manager = app(PrefixedIdManager::class);
        $model = $this->model::class;

        if ($id instanceof Arrayable) {
            $id = $id->toArray();
        }

        if (is_array($id)) {
            return array_map(fn ($value) => $manager->normalizeKeyForModel($value, $model), $id);
        }

        return $manager->normalizeKeyForModel($id, $model);
    }
}
