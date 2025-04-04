<?php
namespace GardenSensors\Config;

class AppConfig {
    private static $config = [];

    public static function get(string $key, $default = null) {
        return self::$config[$key] ?? $default;
    }

    public static function set(string $key, $value): void {
        self::$config[$key] = $value;
    }

    public static function load(array $config): void {
        self::$config = array_merge(self::$config, $config);
    }
} 