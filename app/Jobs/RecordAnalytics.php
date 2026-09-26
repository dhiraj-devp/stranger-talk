<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $userId, public string $name) {}

    public function handle(): void
    {
        AnalyticsEvent::query()->create([
            'user_id' => $this->userId,
            'name' => $this->name,
            'created_at' => now(),
        ]);
    }
}
