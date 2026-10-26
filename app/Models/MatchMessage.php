<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'match_id',
        'sender_id',
        'client_id',
        'body',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(VideoMatch::class, 'match_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
