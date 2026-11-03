<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CloudflareTurn
{
    /**
     * Short-lived ICE servers from Cloudflare. The API token never leaves this class.
     *
     * @return array<int, array<string, mixed>>
     */
    public function iceServers(): array
    {
        $keyId = config('services.cloudflare_turn.key_id');
        $token = config('services.cloudflare_turn.api_token');

        if (! is_string($keyId) || trim($keyId) === '' || ! is_string($token) || trim($token) === '') {
            throw new RuntimeException('Cloudflare TURN is not configured.');
        }

        $url = 'https://rtc.live.cloudflare.com/v1/turn/keys/'.rawurlencode(trim($keyId)).'/credentials/generate-ice-servers';

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->withToken($token)
                ->post($url, ['ttl' => 86400]);
        } catch (ConnectionException) {
            Log::warning('Cloudflare TURN request failed.', ['reason' => 'timeout']);

            throw new RuntimeException('Cloudflare TURN timed out.');
        }

        if (! $response->successful()) {
            Log::warning('Cloudflare TURN request failed.', ['status' => $response->status()]);

            throw new RuntimeException('Cloudflare TURN rejected the request.');
        }

        $servers = $this->normalize($response->json('iceServers'));

        if ($servers === []) {
            Log::warning('Cloudflare TURN response had no usable ice servers.', ['status' => $response->status()]);

            throw new RuntimeException('Cloudflare TURN returned no ice servers.');
        }

        return $servers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalize(mixed $ice): array
    {
        if (! is_array($ice) || $ice === []) {
            return [];
        }

        $list = array_is_list($ice) ? $ice : [$ice];
        $servers = [];

        foreach ($list as $server) {
            if (! is_array($server) || empty($server['urls'])) {
                continue;
            }

            $entry = ['urls' => $server['urls']];

            if (isset($server['username']) && is_string($server['username']) && $server['username'] !== '') {
                $entry['username'] = $server['username'];
            }

            if (isset($server['credential']) && is_string($server['credential']) && $server['credential'] !== '') {
                $entry['credential'] = $server['credential'];
            }

            $servers[] = $entry;
        }

        return $servers;
    }
}
