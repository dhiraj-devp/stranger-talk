<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->check(fn () => DB::select('select 1'));
        $queue = $this->check(fn () => Schema::hasTable('jobs'));
        $redis = 'disabled';
        if (config('platform.redis')) {
            $redis = $this->check(fn () => Redis::ping()) ? 'ok' : 'unavailable';
        }
        $reverb = config('broadcasting.default') === 'reverb' && config('broadcasting.connections.reverb.key')
            ? 'configured'
            : 'not_configured';

        $ok = $database && $queue;
        $body = [
            'app' => 'ok',
            'database' => $database ? 'ok' : 'unavailable',
            'redis' => $redis,
            'queue' => $queue ? 'ok' : 'unavailable',
            'reverb' => $reverb,
        ];

        return response()->json($body, $ok ? 200 : 503);
    }

    private function check(callable $callback): bool
    {
        try {
            return (bool) $callback();
        } catch (\Throwable) {
            return false;
        }
    }
}
