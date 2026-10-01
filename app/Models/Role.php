<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** Built-in role that can manage users and roles. It cannot be renamed or deleted. */
    public const ADMIN = 'admin';

    protected $fillable = [
        'name',
        'description',
    ];

    protected static function booted(): void
    {
        // The admin role is what grants access to user management, so it can never
        // be deleted or renamed (returning false cancels the change).
        static::deleting(fn (Role $role): bool => ! $role->isAdmin());
        static::updating(fn (Role $role): bool => $role->getOriginal('name') !== self::ADMIN || $role->name === self::ADMIN);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    public static function admin(): ?self
    {
        return static::where('name', self::ADMIN)->first();
    }
}
