<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Intrfce\PrefixedUuids\Rules\PublicIdExists;

class PrefixedUuidsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrefixIdRegistry::class);

        $this->app->singleton(PrefixedIdManager::class, function ($app) {
            return new PrefixedIdManager($app->make(PrefixIdRegistry::class));
        });

        $this->app->alias(PrefixedIdManager::class, 'prefixed-id');
    }

    public function boot(): void
    {
        $this->registerValidationRule();
    }

    /**
     * Register the string form `public_id_exists:{table}` (ADR-0015). It
     * delegates to the PublicIdExists rule object so both forms share one
     * implementation.
     */
    private function registerValidationRule(): void
    {
        Validator::extend('public_id_exists', function (string $attribute, mixed $value, array $parameters) {
            $table = $parameters[0] ?? null;

            if ($table === null) {
                throw new \InvalidArgumentException(
                    "The public_id_exists rule requires a table, e.g. 'public_id_exists:customers'."
                );
            }

            // Programmer error (throws) if the table isn't backed by a registered model.
            $model = app(PrefixIdRegistry::class)->modelForTable($table);

            $passed = true;
            PublicIdExists::for($model)->validate(
                $attribute,
                $value,
                function () use (&$passed) {
                    $passed = false;
                }
            );

            return $passed;
        }, 'The selected :attribute is invalid.');
    }
}
