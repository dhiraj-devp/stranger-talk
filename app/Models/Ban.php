<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ban extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'reason', 'notes', 'permanent', 'banned_until', 'lifted_at',
    ];

    protected function casts(): array
    {
        return [
            'permanent' => 'boolean',
            'banned_until' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
