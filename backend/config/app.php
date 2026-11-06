<?php

return [
    'name' => env('APP_NAME', 'Background Job Manager'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'api_key' => env('JOB_MANAGER_API_KEY', ''),
];
