<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'ingest' => [
        'token' => env('INGEST_API_TOKEN'),
    ],

    'google' => [
        'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
        'adsense_client' => env('GOOGLE_ADSENSE_CLIENT'), // e.g. ca-pub-1234567890123456
    ],

    'naver' => [
        'site_verification' => env('NAVER_SITE_VERIFICATION'),
    ],

    'generation' => [
        // Pinged by the admin "지금 생성" button — wakes the AI pipeline to
        // write and publish a fresh batch of articles on demand, instead of
        // waiting for the daily schedule. Tied to a specific session, so it
        // needs refreshing there if it ever stops responding.
        'webhook_url' => env('GENERATION_WEBHOOK_URL'),
    ],

];
