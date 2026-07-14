<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids;

use Illuminate\Support\ServiceProvider;

class PrefixedUuidsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrefixedIdManager::class);
    }
}
