<?php

return [

    /*
    | STUN servers shown on the video page. Comma-separated URLs.
    | TURN credentials are not stored here. The video call asks
    | GET /api/webrtc/ice-servers for a short-lived Cloudflare relay.
    */
    'stun_urls' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WEBRTC_STUN_URLS', 'stun:stun.l.google.com:19302'))
    ))),

];
