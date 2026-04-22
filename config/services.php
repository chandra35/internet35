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

    'genieacs' => [
        'nbi_url' => env('GENIEACS_NBI_URL', 'http://172.10.10.254:7557'),
        'cwmp_url' => env('GENIEACS_CWMP_URL', 'http://172.10.10.254:7547'),
        'cwmp_username' => env('GENIEACS_CWMP_USERNAME', ''),
        'cwmp_password' => env('GENIEACS_CWMP_PASSWORD', ''),
        'ui_url' => env('GENIEACS_UI_URL', 'http://172.10.10.254:3000'),
        'timeout' => env('GENIEACS_TIMEOUT', 10),
    ],

    // Vendor ONU WebUI credentials (used when TR-069 cannot configure WAN VLAN reliably).
    'fiberhome' => [
        'webui_user'     => env('FIBERHOME_WEBUI_USER', 'admin'),
        'webui_password' => env('FIBERHOME_WEBUI_PASSWORD', ''),
    ],

];
