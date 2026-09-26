<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public const ACTIVE = 'active';

    public const OFFLINE = 'offline';

    public const SEARCHING = 'searching';

    public const IN_CALL = 'in_call';

    public const BANNED = 'banned';

    public const GENDERS = ['male', 'female', 'other', 'undisclosed'];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'gender',
        'country_code',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'banned_until' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isBanned(): bool
    {
        if ($this->banned_until && $this->banned_until->isFuture()) {
            return true;
        }

        return $this->status === self::BANNED && $this->banned_until === null;
    }

    public function profileComplete(): bool
    {
        return $this->gender !== null && $this->country_code !== null;
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) config('features.'.$feature, false);
    }

    public function matchPreference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MatchPreference::class);
    }

    public function reportsFiled(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }
}
