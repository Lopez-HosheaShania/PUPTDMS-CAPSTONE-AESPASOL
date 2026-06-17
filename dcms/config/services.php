<?php

return [

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

    'oidc' => [
        'authorize_url' => env('OIDC_AUTHORIZE_URL'),
        'token_url' => env('OIDC_TOKEN_URL'),
        'me_url' => env('OIDC_ME_URL'),
        'logout_url' => env('OIDC_LOGOUT_URL'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect' => env('OIDC_REDIRECT_URI'),
    ],

    'idp' => [
        'login_url' => env('IDP_LOGIN_URL'),
    ],

    'chatbot' => [
        'api_key' => env('CHATBOT_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'signature_model' => env('OPENAI_SIGNATURE_MODEL', 'gpt-5.5'),
    ],

    'signature_ai' => [
        'threshold' => env('SIGNATURE_AI_THRESHOLD', 0.80),
    ],

    'flss' => [
        'base_url' => env('FLSS_BASE_URL', 'https://test-flss.alquatrilixbsit2027.com'),
        'api_url' => env('FLSS_API_URL', 'https://test-flss.alquatrilixbsit2027.com/api/v1'),

        'system' => env('FLSS_SYSTEM', 'dms'),
        'client' => env('FLSS_CLIENT', 'dms'),

        'hmac_secret' => env('FLSS_HMAC_SECRET'),
        'secret' => env('FLSS_HMAC_SECRET'),
    ],

    'hostinger' => [
        'hpanel_url' => env('HOSTINGER_HPANEL_URL', 'https://hpanel.hostinger.com'),
    ],

    'ogos' => [
        'base_url' => env('OGOS_API_BASE_URL'),
        'token_url' => env('OGOS_M2M_TOKEN_URL'),
        'client_id' => env('OGOS_M2M_CLIENT_ID'),
        'client_secret' => env('OGOS_M2M_CLIENT_SECRET'),
        'student_search_path' => env('OGOS_STUDENT_SEARCH_PATH', '/integrations/students/profiles'),
    ],

];
