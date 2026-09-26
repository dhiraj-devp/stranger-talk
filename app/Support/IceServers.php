<?php

namespace App\Support;

class IceServers
{
    /**
     * ICE servers passed to RTCPeerConnection.
     * TURN is included only when WEBRTC_TURN_URL is configured.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function list(): array
    {
        $servers = [];

        foreach (config('webrtc.stun_urls', []) as $url) {
            if (is_string($url) && $url !== '') {
                $servers[] = ['urls' => $url];
            }
        }

        $turnUrl = config('webrtc.turn.url');

        if (! is_string($turnUrl) || trim($turnUrl) === '') {
            return $servers;
        }

        $urls = array_values(array_filter(array_map('trim', explode(',', $turnUrl))));

        if ($urls === []) {
            return $servers;
        }

        $entry = ['urls' => count($urls) === 1 ? $urls[0] : $urls];
        $username = config('webrtc.turn.username');
        $credential = config('webrtc.turn.credential');

        if (is_string($username) && $username !== '') {
            $entry['username'] = $username;
        }

        if (is_string($credential) && $credential !== '') {
            $entry['credential'] = $credential;
        }

        $servers[] = $entry;

        return $servers;
    }
}
