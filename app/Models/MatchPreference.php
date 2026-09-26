<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPreference extends Model
{
    public const ANYONE = 'anyone';

    public const GENDERS = ['anyone', 'male', 'female', 'other'];

    protected $fillable = [
        'user_id',
        'gender_preference',
        'country_preference',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function summary(): string
    {
        $gender = match ($this->gender_preference) {
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
            default => 'Anyone',
        };
        $country = $this->country_preference
            ? \App\Support\Countries::name($this->country_preference)
            : 'Any Country';

        return $gender.' · '.$country;
    }
}
