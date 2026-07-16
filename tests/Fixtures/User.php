<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUuids;

class User extends Model
{
    use HasPrefixedUuids;

    protected $table = 'users';

    protected $guarded = [];

    public function idPrefix(): string
    {
        return 'user';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
