<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMetric extends Model
{
    public $timestamps = false;

    protected $fillable = ['date', 'metric', 'dimension', 'value'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
