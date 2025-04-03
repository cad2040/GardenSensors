<?php

return [
    'enabled' => env('MONITORING_ENABLED', true),
    
    'metrics' => [
        'enabled' => true,
        'driver' => 'prometheus',
        'namespace' => 'garden_sensors',
    ],
    
    'logging' => [
        'enabled' => true,
        'driver' => 'elasticsearch',
        'index' => 'garden-sensors-logs',
        'retention_days' => 30,
    ],
    
    'alerts' => [
        'enabled' => true,
        'channels' => [
            'email' => [
                'enabled' => true,
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
            ],
            'slack' => [
                'enabled' => env('SLACK_ALERTS_ENABLED', false),
                'webhook_url' => env('SLACK_WEBHOOK_URL', ''),
            ],
        ],
        'thresholds' => [
            'sensor_offline' => 300, // 5 minutes
            'high_error_rate' => 0.1, // 10% error rate
            'slow_response' => 1000, // 1 second
        ],
    ],
    
    'performance' => [
        'enabled' => true,
        'driver' => 'newrelic',
        'app_name' => env('NEWRELIC_APP_NAME', 'garden-sensors'),
        'license_key' => env('NEWRELIC_LICENSE_KEY', ''),
    ],
    
    'health_checks' => [
        'database' => [
            'enabled' => true,
            'timeout' => 5,
        ],
        'cache' => [
            'enabled' => true,
            'timeout' => 2,
        ],
        'sensors' => [
            'enabled' => true,
            'timeout' => 10,
            'check_interval' => 60,
        ],
    ],
    
    'dashboards' => [
        'enabled' => true,
        'driver' => 'grafana',
        'url' => env('GRAFANA_URL', ''),
        'api_key' => env('GRAFANA_API_KEY', ''),
    ],
    
    'tracing' => [
        'enabled' => env('TRACING_ENABLED', false),
        'driver' => 'jaeger',
        'endpoint' => env('JAEGER_ENDPOINT', 'http://localhost:14268/api/traces'),
    ],
    
    'profiling' => [
        'enabled' => env('PROFILING_ENABLED', false),
        'driver' => 'blackfire',
        'client_id' => env('BLACKFIRE_CLIENT_ID', ''),
        'client_token' => env('BLACKFIRE_CLIENT_TOKEN', ''),
    ],
]; 