<?php

return [
    'enabled' => env('REQUEST_LOGGER_ENABLED', true),
    'queue' => env('REQUEST_LOGGER_QUEUE', null),
    'store_user_type' => env('REQUEST_LOGGER_STORE_USER_TYPE', false),

    'clear-logs' => [
        'after-days' => 2,
        'limit' => 1000,
    ],

    // These values will be filtered out of the input parameters BEFORE sending the event
    'except-input' => [
        'password',
        'password_confirmation',
    ],
];
