<?php

return [
    'default' => 'daily',
    
    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/garden-sensors.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 30,
        ],
        
        'sensor' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sensor.log'),
            'level' => 'info',
            'days' => 7,
        ],
    ],
    
    'levels' => [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ],
]; 