<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoMatch extends Model
{
    public const WAITING = 'waiting';

    public const MATCHED = 'connecting';

    public const CONNECTING = 'connecting';

    public const CONNECTED = 'connected';

    public const ENDED = 'ended';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    protected $table = 'matches';

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'status',
        'signal',
        'end_reason',
        'user_one_seen_at',
        'user_two_seen_at',
        'user_one_token',
        'user_two_token',
    ];

    protected function casts(): array
    {
        return [
            'signal' => 'array',
            'user_one_seen_at' => 'datetime',
            'user_two_seen_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::WAITING, self::CONNECTING, self::CONNECTED]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId) {
            $query->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
        });
    }

    public function tokenFor(int $userId): ?string
    {
        if ($this->user_one_id === $userId) {
            return $this->user_one_token;
        }

        if ($this->user_two_id === $userId) {
            return $this->user_two_token;
        }

        return null;
    }
}
