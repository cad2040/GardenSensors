<?php
return [
    // Application settings
    'name' => 'Garden Sensors',
    'version' => '1.0.0',
    'debug' => getenv('APP_DEBUG') === 'true',
    'timezone' => 'UTC',
    'url' => getenv('APP_URL'),

    // Session configuration
    'session' => [
        'lifetime' => 120, // minutes
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // Logging configuration
    'log' => [
        'level' => getenv('LOG_LEVEL') ?: 'debug',
        'path' => __DIR__ . '/../logs/app.log',
    ],

    // Cache configuration
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../storage/cache',
    ],

    // Security settings
    'security' => [
        'password_algo' => PASSWORD_DEFAULT,
        'password_options' => [
            'cost' => 12
        ],
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 5,
        'decay_minutes' => 1,
    ],

    // Notification settings
    'notifications' => [
        'email' => [
            'enabled' => true,
            'from_address' => getenv('MAIL_FROM_ADDRESS'),
            'from_name' => getenv('MAIL_FROM_NAME'),
        ],
    ],
]; 