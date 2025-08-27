<?php
namespace GardenSensors\Tests\Services;

use GardenSensors\Tests\TestCase;
use GardenSensors\Services\RateLimiterService;
use GardenSensors\Core\Database;
use Mockery;
use PDO;
use PDOStatement;

class RateLimiterServiceTest extends TestCase
{
    private $rateLimiter;
    private $mockDb;
    private $mockStmt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDb = Mockery::mock(Database::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);
        $this->rateLimiter = new RateLimiterService($this->mockDb);
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
        $this->mockDb->shouldReceive('query')
            ->withAnyArgs()
            ->andReturn([['count' => 0]]);
        
        // Mock the database calls for incrementCount
        $this->mockDb->shouldReceive('execute')
            ->withAnyArgs()
            ->andReturn(true);
        
        $result = $this->rateLimiter->check($userId, $endpoint);
        
        $this->assertTrue($result);
    }

    public function testGetRemainingRequests()
    {
        $userId = 1;
        $endpoint = '/api/sensors';
        
        // Mock the database calls
        $this->mockDb->shouldReceive('query')
            ->withAnyArgs()
            ->andReturn([['count' => 5]]);
        
        $remaining = $this->rateLimiter->getRemainingRequests($userId, $endpoint);
        
        $this->assertIsInt($remaining);
        $this->assertGreaterThanOrEqual(0, $remaining);
    }

    public function testGetResetTime()
    {
        $userId = 1;
        $endpoint = '/api/sensors';
        
        // Since getResetTime doesn't use database in current implementation, no mocking needed
        
        $resetTime = $this->rateLimiter->getResetTime($userId, $endpoint);
        
        $this->assertIsInt($resetTime);
        $this->assertGreaterThan(0, $resetTime);
    }
} 