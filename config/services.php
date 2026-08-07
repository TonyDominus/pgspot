<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'overpass' => [
        'url' => env('OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
        // Fallback mirrors se il primario risponde 406/429/5xx
        'mirrors' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'OVERPASS_MIRRORS',
            'https://overpass.kumi.systems/api/interpreter,https://overpass.openstreetmap.ru/cgi/interpreter'
        ))))),
        'user_agent' => env('OVERPASS_USER_AGENT', 'PGSpot/1.0 (https://www.pgspot.it; info@pgspot.it)'),
        // Su WAMP locale a volte manca il CA bundle: OVERPASS_VERIFY_SSL=false solo in local
        'verify' => filter_var(env('OVERPASS_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
