<?php
namespace GardenSensors\Core;

class Cache {
    private static $instance = null;
    private $cacheDir;

    public function __construct(string $cacheDir)
    {
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $this->cacheDir = $cacheDir;
    }

    public static function getInstance(string $cacheDir): self {
        if (self::$instance === null) {
            self::$instance = new self($cacheDir);
        }
        return self::$instance;
    }

    public function set(string $key, $value, int $ttl = 3600): bool {
        if (empty($key)) {
            throw new \InvalidArgumentException('Cache key cannot be empty');
        }

        if ($ttl <= 0) {
            throw new \InvalidArgumentException('TTL must be greater than 0');
        }

        if ($value === null) {
            throw new \InvalidArgumentException('Cache value cannot be null');
        }

        $cacheData = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        $filename = $this->getCacheFilename($key);
        return file_put_contents($filename, serialize($cacheData)) !== false;
    }

    public function get(string $key) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Cache key cannot be empty');
        }

        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            return null;
        }

        $data = unserialize(file_get_contents($filename));
        if ($data === false) {
            return null;
        }

        if (time() > $data['expires']) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    public function delete(string $key): bool {
        if (empty($key)) {
            throw new \InvalidArgumentException('Cache key cannot be empty');
        }

        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }

    public function clear(?string $key = null): bool {
        if ($key !== null) {
            return $this->delete($key);
        }

        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function clearAll(): bool {
        return $this->clear();
    }

    private function getCacheFilename(string $key): string {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
} 