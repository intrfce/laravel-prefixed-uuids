<?php

declare(strict_types=1);

namespace Intrfce\PrefixedUuids\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Intrfce\PrefixedUuids\Concerns\HasPrefixedUUID;
use Intrfce\PrefixedUuids\PrefixedId;

#[PrefixedId('user')]
class User extends Model
{
    use HasPrefixedUUID;

    protected $table = 'users';

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
