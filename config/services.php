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

    'nanonets' => [
        'api_key' => env('NANONETS_API_KEY'),
        'model_id' => env('NANONETS_MODEL_ID'),
        'base_url' => env('NANONETS_BASE_URL', 'https://app.nanonets.com/api/v2'),
        'validate_connection_on_boot' => env('NANONETS_VALIDATE_CONNECTION_ON_BOOT', false),
        'connection_check_cache_seconds' => (int) env('NANONETS_CONNECTION_CHECK_CACHE_SECONDS', 300),
    ],

];
