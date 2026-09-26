<?php

return [

    /*
    | STUN servers used for the MVP. Comma-separated URLs.
    | TURN is optional and unused until WEBRTC_TURN_URL is set.
    | Credentials stay in the environment; nothing is hard-coded.
    */
    'stun_urls' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WEBRTC_STUN_URLS', 'stun:stun.l.google.com:19302'))
    ))),

    'turn' => [
        'url' => env('WEBRTC_TURN_URL'),
        'username' => env('WEBRTC_TURN_USERNAME'),
        'credential' => env('WEBRTC_TURN_CREDENTIAL'),
    ],

];
