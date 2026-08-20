<?php

return [
    'enabled' => env('REQUEST_LOGGER_ENABLED', true),
    'queue' => env('REQUEST_LOGGER_QUEUE', null),
    'store_user_type' => env('REQUEST_LOGGER_STORE_USER_TYPE', false),

    // Toggle to store the API Token ID (Sanctum/Passport)
    'store_api_token_id' => env('REQUEST_LOGGER_STORE_API_TOKEN_ID', false),
    'store_passport_token_id' => env('REQUEST_LOGGER_STORE_PASSPORT_TOKEN_ID', false),

    'clear-logs' => [
        'after-days' => 2,
        'limit' => 1000,
    ],

    // These values will be filtered out of the input parameters BEFORE sending the event
    'except-input' => [
        'password',
        'password_confirmation',
    ],

    // These headers are still recorded, but their value is replaced with
    // "[redacted]" BEFORE sending the event, so credentials never reach the
    // request_logs table. Matching is case-insensitive.
    //'redact-headers' => [
    //    'authorization',
    //    'proxy-authorization',
    //    'cookie',
    //    'set-cookie',
    //    'x-api-key',
    //    'x-auth-token',
    //    'x-xsrf-token',
    //    'php-auth-pw',
    //],
];
