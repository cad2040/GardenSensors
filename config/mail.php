<?php
return [
    'default' => getenv('MAIL_MAILER') ?: 'smtp',

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => getenv('MAIL_HOST'),
            'port' => getenv('MAIL_PORT') ?: 587,
            'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
            'username' => getenv('MAIL_USERNAME'),
            'password' => getenv('MAIL_PASSWORD'),
            'timeout' => null,
            'auth_mode' => null,
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => '/usr/sbin/sendmail -bs',
        ],
    ],

    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS'),
        'name' => getenv('MAIL_FROM_NAME'),
    ],

    'reply_to' => [
        'address' => getenv('MAIL_REPLY_TO_ADDRESS') ?: getenv('MAIL_FROM_ADDRESS'),
        'name' => getenv('MAIL_REPLY_TO_NAME') ?: getenv('MAIL_FROM_NAME'),
    ],

    // Email templates
    'templates' => [
        'path' => __DIR__ . '/../templates/emails',
    ],

    // Email queue settings
    'queue' => [
        'enabled' => true,
        'connection' => 'database',
        'table' => 'email_queue',
        'retry_after' => 90,
        'max_attempts' => 3,
    ],
]; 