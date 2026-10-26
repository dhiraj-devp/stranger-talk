<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'first_path', 'referrer_host', 'device', 'country', 'ip_address', 'started_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime'];
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }
}
