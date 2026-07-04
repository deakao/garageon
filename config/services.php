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

    'vehicle_plate' => [
        'provider' => env('VEHICLE_PLATE_PROVIDER', 'apiplacas'),
        'apiplacas_token' => env('APIPLACAS_TOKEN'),
        'apiplacas_url' => env('APIPLACAS_URL', 'https://wdapi2.com.br/consulta'),
        'brasildados_token' => env('BRASILDADOS_TOKEN'),
        'brasildados_url' => env('BRASILDADOS_VEHICLE_PLATE_URL', 'https://brasildados--consulta-veiculo-por-placa.apify.actor/check'),
    ],

];
