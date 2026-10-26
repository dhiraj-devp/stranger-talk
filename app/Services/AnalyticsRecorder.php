<?php

namespace App\Services;

use App\Jobs\RecordAnalytics;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AnalyticsRecorder
{
    public function track(?User $user, string $name, array $meta = []): void
    {
        try {
            RecordAnalytics::dispatch($user?->id, $name, $meta);
        } catch (\Throwable $e) {
            Log::warning('Analytics dispatch failed.', ['event' => $name]);
        }
    }
}
