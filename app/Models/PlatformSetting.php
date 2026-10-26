<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['preference_matching', 'limits'];

    protected function casts(): array
    {
        return [
            'preference_matching' => 'boolean',
            'limits' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
