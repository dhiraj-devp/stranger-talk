<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PresenceService
{
    private static bool $unavailable = false;

    public function enabled(): bool
    {
        return (bool) config('platform.redis') && ! self::$unavailable;
    }

    public function touch(int $userId, string $state): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            Redis::setex("presence:user:{$userId}", 45, $state);
            if ($state === 'searching') {
                Redis::sadd('matchmaking:waiting', (string) $userId);
            } else {
                Redis::srem('matchmaking:waiting', (string) $userId);
            }
        } catch (\Throwable $e) {
            self::$unavailable = true;
            Log::warning('Redis presence unavailable.');
        }
    }

    public function waitingCount(): ?int
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            return (int) Redis::scard('matchmaking:waiting');
        } catch (\Throwable $e) {
            self::$unavailable = true;
            Log::warning('Redis presence unavailable.');

            return null;
        }
    }
}
