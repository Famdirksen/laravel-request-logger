<?php

return [
    'enabled' => env('REQUEST_LOGGER_ENABLED', true),

    'queue' => env('REQUEST_LOGGER_QUEUE', null),

    // These values will be filtered out of the input parameters BEFORE sending the event
    'except-input' => [
        'password',
        'password_confirmation',
    ],
];
