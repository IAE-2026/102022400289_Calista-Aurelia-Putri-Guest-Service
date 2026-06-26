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

    'iae' => [
        'api_key' => env('IAE_API_KEY', '102022400289'),
        'service_name' => env('SERVICE_NAME', 'Guest-Service'),
        'api_version' => env('API_VERSION', 'v1'),
        'team_id' => env('CENTRAL_TEAM_ID', 'TEAM-11'),
        'soap_url' => env('CENTRAL_SOAP_AUDIT_URL', 'https://iae-sso.virtualfri.id/soap/v1/audit'),
        'rabbitmq_url' => env('CENTRAL_RABBITMQ_BRIDGE_URL', 'https://iae-sso.virtualfri.id/api/v1/messages/publish'),
        'central_api_key' => env('CENTRAL_API_KEY', 'KEY-MHS-346'),
        'sso_jwks_url' => env('CENTRAL_SSO_JWKS_URL', 'https://iae-sso.virtualfri.id/api/v1/auth/jwks'),
    ],

];
