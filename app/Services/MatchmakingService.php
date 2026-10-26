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

        if (in_array($partner->id, $this->safety->blockedIds($seeker->id), true)) {
            return false;
        }

        if (! config('features.preference_matching')) {
            return true;
        }

        return $this->accepts($seeker, $partner) && $this->accepts($partner, $seeker);
    }

    private function accepts(User $chooser, User $candidate): bool
    {
        $preference = $chooser->matchPreference()->first();
        if (! $preference) {
            return true;
        }

        $gender = $preference->gender_preference ?: 'anyone';
        if ($gender !== 'anyone' && $gender !== $candidate->gender) {
            return false;
        }

        return ! $preference->country_preference || $preference->country_preference === $candidate->country_code;
    }
}
