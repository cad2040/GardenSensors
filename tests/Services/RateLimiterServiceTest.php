<?php
namespace GardenSensors\Tests\Services;

use GardenSensors\Tests\TestCase;
use GardenSensors\Services\RateLimiterService;
use GardenSensors\Services\DatabaseService;
use Mockery;
use PDO;
use PDOStatement;

class RateLimiterServiceTest extends TestCase
{
    private $rateLimiter;
    private $mockDbService;
    private $mockStmt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDbService = Mockery::mock(DatabaseService::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);
        $this->rateLimiter = new RateLimiterService($this->mockDbService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRateLimiterInitialization()
    {
        $this->assertInstanceOf(RateLimiterService::class, $this->rateLimiter);
    }

    public function testCheckRequest()
    {
        $userId = 1;
        $endpoint = '/api/sensors';
        
        // Mock the database calls for getCurrentCount
        $this->mockDbService->shouldReceive('prepare')
            ->with(Mockery::pattern('/SELECT COUNT\*\) as count FROM rate_limits/'))
            ->andReturn($this->mockStmt);
        
        $this->mockStmt->shouldReceive('execute')
            ->with([$userId, $endpoint, Mockery::any()])
            ->andReturn(true);
        
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 0]);
        
        // Mock the database calls for incrementCount
        $this->mockDbService->shouldReceive('prepare')
            ->with(Mockery::pattern('/INSERT INTO rate_limits/'))
            ->andReturn($this->mockStmt);
        
        $this->mockStmt->shouldReceive('execute')
            ->with([$userId, $endpoint])
            ->andReturn(true);
        
        $result = $this->rateLimiter->check($userId, $endpoint);
        
        $this->assertTrue($result);
    }

    public function testGetRemainingRequests()
    {
        $userId = 1;
        $endpoint = '/api/sensors';
        
        // Mock the database calls
        $this->mockDbService->shouldReceive('prepare')
            ->with(Mockery::pattern('/SELECT COUNT\*\) as count FROM rate_limits/'))
            ->andReturn($this->mockStmt);
        
        $this->mockStmt->shouldReceive('execute')
            ->with([$userId, $endpoint, Mockery::any()])
            ->andReturn(true);
        
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 5]);
        
        $remaining = $this->rateLimiter->getRemainingRequests($userId, $endpoint);
        
        $this->assertIsInt($remaining);
        $this->assertGreaterThanOrEqual(0, $remaining);
    }

    public function testGetResetTime()
    {
        $userId = 1;
        $endpoint = '/api/sensors';
        
        // Mock the database calls
        $this->mockDbService->shouldReceive('prepare')
            ->with(Mockery::pattern('/SELECT MAX\(timestamp\) as last_request/'))
            ->andReturn($this->mockStmt);
        
        $this->mockStmt->shouldReceive('execute')
            ->with([$userId, $endpoint])
            ->andReturn(true);
        
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['last_request' => '2023-01-01 12:00:00']);
        
        $resetTime = $this->rateLimiter->getResetTime($userId, $endpoint);
        
        $this->assertIsInt($resetTime);
        $this->assertGreaterThan(0, $resetTime);
    }
} 