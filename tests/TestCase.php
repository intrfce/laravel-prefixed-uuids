<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Intrfce\PrefixedUuids\Facades\PrefixedId;
use Intrfce\PrefixedUuids\PrefixIdRegistry;
use Intrfce\PrefixedUuids\PrefixedUuidsServiceProvider;
use Intrfce\PrefixedUuids\Tests\Fixtures\Customer;
use Intrfce\PrefixedUuids\Tests\Fixtures\Post;
use Intrfce\PrefixedUuids\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();

        // The registry singleton persists across tests in one process — reset it.
        app(PrefixIdRegistry::class)->flush();

        PrefixedId::map([
            'user' => User::class,
            'cus' => Customer::class,
            'post' => Post::class,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [PrefixedUuidsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
