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

    /*
    |--------------------------------------------------------------------------
    | MRA Electronic Invoicing System (EIS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Malawi Revenue Authority's Electronic Invoicing System.
    | This enables automatic fiscalization of sales receipts as required by
    | MRA for VAT-registered businesses.
    |
    | Documentation: https://eis-api.mra.mw/docs/
    | Developer Portal: https://eis-portal.mra.mw/Home/DeveloperResources
    |
    */

    'mra_eis' => [
        'enabled' => env('MRA_EIS_ENABLED', false),
        'base_url' => env('MRA_EIS_BASE_URL', 'https://eis-api.mra.mw'),
        'client_id' => env('MRA_EIS_CLIENT_ID'),
        'client_secret' => env('MRA_EIS_CLIENT_SECRET'),
        'timeout' => env('MRA_EIS_TIMEOUT', 30),
        'auto_fiscalize' => env('MRA_EIS_AUTO_FISCALIZE', true),
        'retry_failed' => env('MRA_EIS_RETRY_FAILED', true),
        'max_retries' => env('MRA_EIS_MAX_RETRIES', 3),
    ],

];
