<?php

namespace App\Support;

class IceServers
{
    /**
     * STUN servers for the video page. TURN is not included.
     * The call loads a fresh Cloudflare relay from /api/webrtc/ice-servers.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function list(): array
    {
        $servers = [];
        $configured = [];

        foreach (config('webrtc.stun_urls', []) as $url) {
            if (is_string($url) && $url !== '') {
                $configured[] = $url;
            }
        }

        foreach ([
            'stun:stun.l.google.com:19302',
            'stun:stun1.l.google.com:19302',
            'stun:stun.cloudflare.com:3478',
        ] as $url) {
            if (! in_array($url, $configured, true)) {
                $configured[] = $url;
            }
        }

        foreach ($configured as $url) {
            $servers[] = ['urls' => $url];
        }

        return $servers;
    }
}
