<?php

return [
    'python' => [
        'path' => dirname(dirname(dirname(__DIR__))) . '/venv/bin/python3',
        'scripts_dir' => dirname(dirname(dirname(__DIR__))) . '/python',
        'plots_dir' => dirname(dirname(dirname(__DIR__))) . '/public/plots',
        'log_dir' => dirname(dirname(dirname(__DIR__))) . '/logs',
        'options' => [
            'timeout' => 30,
            'memory_limit' => '256M',
            'error_reporting' => true
        ]
    ]
]; 