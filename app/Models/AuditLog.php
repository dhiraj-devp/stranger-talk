<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'target_type', 'target_id', 'metadata', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function write(?User $admin, string $action, ?Model $target = null, array $metadata = [], ?string $ip = null): void
    {
        static::query()->create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
