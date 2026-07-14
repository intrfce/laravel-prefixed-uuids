<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Illuminate\Database\Eloquent\Model;
use Intrfce\PrefixedUuids\Exceptions\DuplicatePrefixException;
use Intrfce\PrefixedUuids\Exceptions\ModelNotRegisteredException;
use Intrfce\PrefixedUuids\Exceptions\UnknownPrefixException;

/**
 * The single source of truth for prefix <-> model mappings, populated
 * morph-map style via PrefixedId::map([...]) (ADR-0011). Maintains three
 * indexes so every lookup direction is O(1), and enforces uniqueness at build
 * time (ADR-0012 / 0015).
 *
 * @var array<string, class-string<Model>> $prefixToModel
 * @var array<class-string<Model>, string> $modelToPrefix
 * @var array<string, class-string<Model>> $tableToModel
 */
class PrefixIdRegistry
{
    private array $prefixToModel = [];

    private array $modelToPrefix = [];

    private array $tableToModel = [];

    /**
     * Register a batch of prefix => model mappings. Conflicting remaps throw.
     *
     * @param  array<string, class-string<Model>>  $map
     */
    public function map(array $map): void
    {
        foreach ($map as $prefix => $model) {
            $this->register($prefix, $model);
        }
    }

    private function register(string $prefix, string $model): void
    {
        if (isset($this->prefixToModel[$prefix]) && $this->prefixToModel[$prefix] !== $model) {
            throw DuplicatePrefixException::prefix($prefix, $this->prefixToModel[$prefix], $model);
        }

        if (isset($this->modelToPrefix[$model]) && $this->modelToPrefix[$model] !== $prefix) {
            throw DuplicatePrefixException::model($model, $this->modelToPrefix[$model], $prefix);
        }

        $table = (new $model)->getTable();

        if (isset($this->tableToModel[$table]) && $this->tableToModel[$table] !== $model) {
            throw DuplicatePrefixException::table($table, $this->tableToModel[$table], $model);
        }

        $this->prefixToModel[$prefix] = $model;
        $this->modelToPrefix[$model] = $prefix;
        $this->tableToModel[$table] = $model;
    }

    /** @return class-string<Model> */
    public function modelForPrefix(string $prefix): string
    {
        return $this->prefixToModel[$prefix]
            ?? throw UnknownPrefixException::make($prefix);
    }

    /** @param  class-string<Model>  $model */
    public function prefixForModel(string $model): string
    {
        return $this->modelToPrefix[$model]
            ?? throw ModelNotRegisteredException::model($model);
    }

    /** @return class-string<Model> */
    public function modelForTable(string $table): string
    {
        return $this->tableToModel[$table]
            ?? throw ModelNotRegisteredException::table($table);
    }

    public function hasPrefix(string $prefix): bool
    {
        return isset($this->prefixToModel[$prefix]);
    }

    /** Discard all registrations (primarily for test isolation). */
    public function flush(): void
    {
        $this->prefixToModel = [];
        $this->modelToPrefix = [];
        $this->tableToModel = [];
    }
}
