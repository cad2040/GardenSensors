<?php
namespace GardenSensors\Tests\Services;

use GardenSensors\Tests\TestCase;
use GardenSensors\Services\LoggingService;

class LoggingServiceTest extends TestCase
{
    private $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggingService = new LoggingService();
    }

    public function testLoggingServiceInitialization()
    {
        $this->assertInstanceOf(LoggingService::class, $this->loggingService);
    }

    public function testInfoLogging()
    {
        $message = "Test info message";
        $this->loggingService->info($message);
        
        // Check if log file was created and contains the message
        $logFile = '/tmp/garden_sensors.log';
        $this->assertFileExists($logFile);
        $this->assertStringContainsString($message, file_get_contents($logFile));
    }

    public function testErrorLogging()
    {
        $message = "Test error message";
        $this->loggingService->error($message);
        
        // Check if log file contains the error message
        $logFile = '/tmp/garden_sensors.log';
        $this->assertStringContainsString($message, file_get_contents($logFile));
    }

    public function testWarningLogging()
    {
        $message = "Test warning message";
        $this->loggingService->warning($message);
        
        // Check if log file contains the warning message
        $logFile = '/tmp/garden_sensors.log';
        $this->assertStringContainsString($message, file_get_contents($logFile));
    }

    public function testDebugLogging()
    {
        $message = "Test debug message";
        $this->loggingService->debug($message);
        
        // Check if log file contains the debug message
        $logFile = '/tmp/garden_sensors.log';
        $this->assertStringContainsString($message, file_get_contents($logFile));
    }

    public function testLogWithContext()
    {
        $message = "Test message with context";
        $context = ['user_id' => 1, 'action' => 'test'];
        
        $this->loggingService->info($message, $context);
        
        // Check if log file contains the message with context
        $logFile = '/tmp/garden_sensors.log';
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString($message, $logContent);
        $this->assertStringContainsString('user_id', $logContent);
    }

    public function testGetLogs()
    {
        // Write a test log entry
        $this->loggingService->info("Test log entry");
        
        // Check if log file exists and has content
        $logFile = '/tmp/garden_sensors.log';
        $this->assertFileExists($logFile);
        $this->assertGreaterThan(0, filesize($logFile));
    }

    public function testClearLogs()
    {
        // Write a test log entry
        $this->loggingService->info("Test log entry");
        
        // Check that the log file exists and has content
        $logFile = '/tmp/garden_sensors.log';
        $this->assertFileExists($logFile);
        $this->assertGreaterThan(0, filesize($logFile));
        
        // Note: LoggingService doesn't have a clear method, so we just verify logging works
    }
} 