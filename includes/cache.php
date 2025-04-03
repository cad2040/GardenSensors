<?php
class Cache {
    private $cacheDir;
    private $enabled;

    public function __construct() {
        $this->cacheDir = CACHE_DIR;
        $this->enabled = CACHE_ENABLED;
        
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get($key) {
        if (!$this->enabled) {
            return false;
        }

        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return false;
        }

        $data = file_get_contents($file);
        $cache = unserialize($data);

        if ($cache['expires'] < time()) {
            $this->delete($key);
            return false;
        }

        return $cache['data'];
    }

    public function set($key, $data, $ttl = CACHE_TTL) {
        if (!$this->enabled) {
            return false;
        }

        $file = $this->getCacheFile($key);
        $cache = [
            'expires' => time() + $ttl,
            'data' => $data
        ];

        return file_put_contents($file, serialize($cache));
    }

    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function clear() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
} 