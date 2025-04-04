<?php
namespace GardenSensors\Core;

class Settings {
    private static $instance = null;
    private $settings = [];
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSettings(): void {
        $sql = "SELECT * FROM settings";
        $this->settings = $this->db->query($sql);
    }

    public function get(string $key, $default = null) {
        // First try to get from database
        $sql = "SELECT value FROM settings WHERE `key` = ?";
        $result = $this->db->query($sql, [$key]);
        if (!empty($result)) {
            return $result[0]['value'];
        }

        // If not found in database, check memory cache
        foreach ($this->settings as $setting) {
            if ($setting['key'] === $key) {
                return $setting['value'];
            }
        }

        return $default;
    }

    public function set(string $key, $value): bool {
        if (!$this->validateSetting($key, $value)) {
            throw new \InvalidArgumentException("Invalid setting value for $key");
        }

        $sql = "INSERT INTO settings (`key`, `value`, updated_at) VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()";
        
        try {
            $this->db->execute($sql, [$key, $value, $value]);
            $this->loadSettings();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(string $key): bool {
        $sql = "DELETE FROM settings WHERE `key` = ?";
        
        try {
            $this->db->execute($sql, [$key]);
            $this->loadSettings();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateInterval(int $minutes): bool {
        if ($minutes < 1 || $minutes > 60) {
            return false;
        }
        return $this->set('update_interval', $minutes);
    }

    public function timezone(string $timezone): bool {
        if (!in_array($timezone, timezone_identifiers_list())) {
            return false;
        }
        return $this->set('timezone', $timezone);
    }

    public function reset(): bool {
        $sql = "DELETE FROM settings";
        
        try {
            $this->db->execute($sql);
            $this->settings = [];
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateSetting(string $key, $value): bool
    {
        switch ($key) {
            case 'update_interval':
                return is_numeric($value) && $value > 0;
            case 'timezone':
                return in_array($value, \DateTimeZone::listIdentifiers());
            default:
                return true;
        }
    }
} 