<?php

return [
    'mode' => env('AI_MODE', 'normal'),

    'features' => [
        'chatbot' => [
            'enabled' => env('AI_CHATBOT_ENABLED', true),
            'provider' => 'openai',
        ],
        'signature' => [
            'enabled' => env('AI_SIGNATURE_ENABLED', true),
            'provider' => 'openai',
        ],
    ],
];
