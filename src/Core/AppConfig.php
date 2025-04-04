<?php

namespace GardenSensors\Config;

class AppConfig {
    // Database configuration
    public const DB_HOST = 'localhost';
    public const DB_NAME = 'garden_sensors';
    public const DB_USER = 'root';
    public const DB_PASS = '';
    public const DB_CHARSET = 'utf8mb4';

    // Application configuration
    public const APP_NAME = 'Garden Sensors';
    public const APP_URL = 'http://localhost/GardenSensors/GUI';
    public const APP_VERSION = '1.0.0';

    // Security configuration
    public const SESSION_LIFETIME = 3600; // 1 hour
    public const CSRF_TOKEN_LENGTH = 32;
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOGIN_TIMEOUT = 900; // 15 minutes
    public const API_KEY_LENGTH = 32;

    // Cache configuration
    public const CACHE_ENABLED = true;
    public const CACHE_DIR = __DIR__ . '/../../cache';
    public const CACHE_TTL = 300; // 5 minutes

    // Rate limiting configuration
    public const RATE_LIMIT_ENABLED = true;
    public const RATE_LIMIT_REQUESTS = 100; // requests per minute
    public const RATE_LIMIT_WINDOW = 60; // 1 minute window

    // API configuration
    public const API_VERSION = 'v1';
    public const API_BASE_URL = self::APP_URL . '/api/' . self::API_VERSION;
    public const API_RESPONSE_FORMAT = 'json';

    // Logging configuration
    public const LOG_LEVEL = 'debug'; // debug, info, warning, error
    public const LOG_FILE = __DIR__ . '/../../logs/app.log';
    public const LOG_MAX_SIZE = 5242880; // 5MB
    public const LOG_MAX_FILES = 5;

    public static function initialize(): void {
        // Error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', self::LOG_FILE);

        // Time zone
        date_default_timezone_set('UTC');

        // Create necessary directories
        self::createDirectories();
    }

    private static function createDirectories(): void {
        $directories = [
            dirname(self::CACHE_DIR),
            dirname(self::LOG_FILE),
            self::CACHE_DIR
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    public static function getDatabaseConfig(): array {
        return [
            'host' => self::DB_HOST,
            'dbname' => self::DB_NAME,
            'user' => self::DB_USER,
            'password' => self::DB_PASS,
            'charset' => self::DB_CHARSET
        ];
    }

    public static function getCacheConfig(): array {
        return [
            'enabled' => self::CACHE_ENABLED,
            'directory' => self::CACHE_DIR,
            'ttl' => self::CACHE_TTL
        ];
    }

    public static function getRateLimitConfig(): array {
        return [
            'enabled' => self::RATE_LIMIT_ENABLED,
            'requests' => self::RATE_LIMIT_REQUESTS,
            'window' => self::RATE_LIMIT_WINDOW
        ];
    }

    public static function getLoggingConfig(): array {
        return [
            'level' => self::LOG_LEVEL,
            'file' => self::LOG_FILE,
            'max_size' => self::LOG_MAX_SIZE,
            'max_files' => self::LOG_MAX_FILES
        ];
    }
} 