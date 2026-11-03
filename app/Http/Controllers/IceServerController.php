<?php

namespace App\Http\Controllers;

use App\Services\CloudflareTurn;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class IceServerController extends Controller
{
    public function show(CloudflareTurn $turn): JsonResponse
    {
        try {
            $servers = $turn->iceServers();
        } catch (RuntimeException $exception) {
            $missing = $exception->getMessage() === 'Cloudflare TURN is not configured.';

            return response()->json([
                'message' => $missing
                    ? 'Video relay is not configured.'
                    : 'Video relay is unavailable. Try again in a moment.',
            ], $missing ? 503 : 502)->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'iceServers' => $servers,
        ])->header('Cache-Control', 'no-store');
    }
}
