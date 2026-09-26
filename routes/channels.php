<?php

use App\Models\VideoMatch;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('signals.{token}', function ($user, string $token) {
    return VideoMatch::query()
        ->forUser($user->id)
        ->where(function ($query) use ($user, $token) {
            $query->where(fn ($q) => $q->where('user_one_id', $user->id)->where('user_one_token', $token))
                ->orWhere(fn ($q) => $q->where('user_two_id', $user->id)->where('user_two_token', $token));
        })
        ->exists();
});
