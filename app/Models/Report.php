<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const PENDING = 'pending';

    public const REVIEWING = 'reviewing';

    public const RESOLVED = 'resolved';

    public const REJECTED = 'rejected';

    public const REASONS = [
        'harassment',
        'nudity',
        'spam',
        'abuse',
        'suspicious',
        'other',
    ];

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'match_id',
        'reason',
        'description',
        'status',
        'moderator_id',
        'moderator_notes',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
