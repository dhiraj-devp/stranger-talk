<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordAnalytics implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $userId, public string $name, public array $meta = []) {}

    public function handle(): void
    {
        AnalyticsEvent::query()->create([
            'user_id' => $this->userId,
            'name' => $this->name,
            'meta' => $this->meta === [] ? null : $this->meta,
            'created_at' => now(),
        ]);
    }
}
