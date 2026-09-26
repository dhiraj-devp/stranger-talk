<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    public const CREATED = 'match_created';

    public const ACCEPTED = 'match_accepted';

    public const STARTED = 'connection_started';

    public const CONNECTED = 'connection_connected';

    public const FAILED = 'connection_failed';

    public const NEXT = 'next_clicked';

    public const ENDED = 'call_ended';

    public const DISCONNECT = 'disconnect';

    public const TIMEOUT = 'timeout';

    public $timestamps = false;

    protected $fillable = ['match_id', 'user_id', 'type', 'meta', 'created_at'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(VideoMatch::class, 'match_id');
    }

    public static function write(?int $matchId, ?int $userId, string $type, array $meta = []): void
    {
        static::query()->create([
            'match_id' => $matchId,
            'user_id' => $userId,
            'type' => $type,
            'meta' => $meta === [] ? null : $meta,
            'created_at' => now(),
        ]);
    }
}
