<?php

namespace App\Services;

use App\Jobs\RecordAnalytics;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AnalyticsRecorder
{
    public function track(?User $user, string $name): void
    {
        try {
            RecordAnalytics::dispatch($user?->id, $name);
        } catch (\Throwable $e) {
            Log::warning('Analytics dispatch failed.', ['event' => $name]);
        }
    }
}
