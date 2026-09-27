<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $guarded = [];

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
