<?php
namespace GardenSensors\Tests\Services;

use GardenSensors\Tests\TestCase;
use GardenSensors\Services\RateLimiterService;
use GardenSensors\Services\DatabaseService;
use Mockery;

class RateLimiterServiceTest extends TestCase
{
    private $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Mockery::mock(DatabaseService::class);
        $this->rateLimiter = new RateLimiterService($this->db);
    }

    public function testRateLimiterInitialization()
    {
        $this->assertInstanceOf(RateLimiterService::class, $this->rateLimiter);
    }

    public function testAllowRequest()
    {
        $ip = '192.168.1.1';
        $endpoint = '/api/sensors';
        
        $result = $this->rateLimiter->allowRequest($ip, $endpoint);
        
        $this->assertTrue($result);
    }

    public function testRateLimitExceeded()
    {
        $ip = '192.168.1.2';
        $endpoint = '/api/sensors';
        
        // Make multiple requests to trigger rate limit
        for ($i = 0; $i < 100; $i++) {
            $this->rateLimiter->allowRequest($ip, $endpoint);
        }
        
        $result = $this->rateLimiter->allowRequest($ip, $endpoint);
        
        $this->assertFalse($result);
    }

    public function testGetRemainingRequests()
    {
        $ip = '192.168.1.3';
        $endpoint = '/api/sensors';
        
        $remaining = $this->rateLimiter->getRemainingRequests($ip, $endpoint);
        
        $this->assertIsInt($remaining);
        $this->assertGreaterThan(0, $remaining);
    }

    public function testResetRateLimit()
    {
        $ip = '192.168.1.4';
        $endpoint = '/api/sensors';
        
        $result = $this->rateLimiter->resetRateLimit($ip, $endpoint);
        
        $this->assertTrue($result);
    }

    public function testDifferentEndpoints()
    {
        $ip = '192.168.1.5';
        $endpoint1 = '/api/sensors';
        $endpoint2 = '/api/readings';
        
        $result1 = $this->rateLimiter->allowRequest($ip, $endpoint1);
        $result2 = $this->rateLimiter->allowRequest($ip, $endpoint2);
        
        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function testRateLimitConfiguration()
    {
        $config = [
            'max_requests' => 10,
            'time_window' => 60
        ];
        
        $this->rateLimiter->setConfiguration($config);
        
        $this->assertEquals(10, $this->rateLimiter->getMaxRequests());
        $this->assertEquals(60, $this->rateLimiter->getTimeWindow());
    }
} 