<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Cache;
use PHPUnit\Framework\TestCase;
use Mockery;

class CacheTest extends TestCase
{
    private $cache;
    private $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary cache directory
        $this->cacheDir = sys_get_temp_dir() . '/garden_sensors_cache_' . uniqid();
        mkdir($this->cacheDir, 0777, true);
        
        // Create cache instance
        $this->cache = new Cache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        // Clean up cache directory
        $this->recursiveDelete($this->cacheDir);
        parent::tearDown();
    }

    private function recursiveDelete($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }

        rmdir($dir);
    }

    public function testSetAndGet()
    {
        $key = 'test_key';
        $value = ['data' => 'test_value'];
        $ttl = 60;

        // Test setting cache
        $result = $this->cache->set($key, $value, $ttl);
        $this->assertTrue($result);

        // Test getting cache
        $cachedValue = $this->cache->get($key);
        $this->assertEquals($value, $cachedValue);
    }

    public function testGetExpired()
    {
        $key = 'test_key';
        $value = ['data' => 'test_value'];
        $ttl = 1; // 1 second TTL

        // Set cache with short TTL
        $this->cache->set($key, $value, $ttl);

        // Wait for cache to expire
        sleep(2);

        // Try to get expired cache
        $cachedValue = $this->cache->get($key);
        $this->assertNull($cachedValue);
    }

    public function testClear()
    {
        $key = 'test_key';
        $value = ['data' => 'test_value'];

        // Set cache
        $this->cache->set($key, $value, 60);

        // Clear cache
        $result = $this->cache->clear($key);
        $this->assertTrue($result);

        // Verify cache is cleared
        $cachedValue = $this->cache->get($key);
        $this->assertNull($cachedValue);
    }

    public function testClearAll()
    {
        // Set multiple cache entries
        $this->cache->set('key1', 'value1', 60);
        $this->cache->set('key2', 'value2', 60);

        // Clear all cache
        $result = $this->cache->clearAll();
        $this->assertTrue($result);

        // Verify all cache is cleared
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testGetNonExistent()
    {
        $key = 'non_existent_key';
        $value = $this->cache->get($key);
        $this->assertNull($value);
    }

    public function testSetWithInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache key');

        $this->cache->set('', 'value', 60);
    }

    public function testSetWithInvalidTTL()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid TTL');

        $this->cache->set('key', 'value', -1);
    }

    public function testSetWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache value');

        $this->cache->set('key', null, 60);
    }

    public function testSetWithLargeValue()
    {
        $key = 'large_key';
        $value = str_repeat('x', 1024 * 1024); // 1MB string

        $result = $this->cache->set($key, $value, 60);
        $this->assertTrue($result);

        $cachedValue = $this->cache->get($key);
        $this->assertEquals($value, $cachedValue);
    }
} 