<?php

namespace App\Services;

use App\Models\User;

class MatchmakingService
{
    public function __construct(private SafetyService $safety) {}

    public function canPair(User $seeker, ?User $partner): bool
    {
        if (! $partner || $seeker->id === $partner->id) {
            return false;
        }

        if ($seeker->isBanned() || $partner->isBanned()) {
            return false;
        }

        return ! in_array($partner->id, $this->safety->blockedIds($seeker->id), true);
    }
}
