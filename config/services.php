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

    'payment_gateway' => [
        'initiate_url' => env('PAYMENT_GATEWAY_INITIATE_URL')
            ?: rtrim((string) env('PAYMENT_GATEWAY_BASE_URL'), '/').'/'.ltrim((string) env('PAYMENT_GATEWAY_ENDPOINT', '/api/v1/payment'), '/'),
        'secret_key' => env('SECRET_KEY', env('PAYMENT_GATEWAY_TOKEN')),
        'client_key' => env('CLIENT_KEY'),
        'app_id' => env('APP_ID'),
        'channel_code' => env('CHANNEL_CODE', env('API_KSS_PAYMENT_CHANNEL_CODE')),
        'channels' => [
            'qris' => env('PAYMENT_CHANNEL_QRIS', env('CHANNEL_CODE', env('API_KSS_PAYMENT_CHANNEL_CODE', 'DEVQRIS'))),
            'bsi' => env('PAYMENT_CHANNEL_BSI', 'FINBSIVA'),
            'bni' => env('PAYMENT_CHANNEL_BNI', 'BNI'),
            'permata' => env('PAYMENT_CHANNEL_PERMATA', 'PERMATA'),
            'mandiri' => env('PAYMENT_CHANNEL_MANDIRI', 'MANDIRI'),
        ],
        'callback_url' => env('PAYMENT_GATEWAY_CALLBACK_URL'),
    ],

];
