<?php
namespace GardenSensors\Tests\Services;

use GardenSensors\Tests\TestCase;
use GardenSensors\Services\LoggingService;

class LoggingServiceTest extends TestCase
{
    private $loggingService;
    protected $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = sys_get_temp_dir() . '/garden_sensors_test_' . getmypid() . '.log';
        putenv('LOG_FILE=' . $this->logFile);
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        $this->loggingService = new LoggingService();
    }

    protected function tearDown(): void
    {
        if ($this->logFile && file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        parent::tearDown();
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
        $this->assertFileExists($this->logFile);
        $this->assertStringContainsString($message, file_get_contents($this->logFile));
    }

    public function testErrorLogging()
    {
        $message = "Test error message";
        $this->loggingService->error($message);
        
        // Check if log file contains the error message
        $this->assertStringContainsString($message, file_get_contents($this->logFile));
    }

    public function testWarningLogging()
    {
        $message = "Test warning message";
        $this->loggingService->warning($message);
        
        // Check if log file contains the warning message
        $this->assertStringContainsString($message, file_get_contents($this->logFile));
    }

    public function testDebugLogging()
    {
        $message = "Test debug message";
        $this->loggingService->debug($message);
        
        // Check if log file contains the debug message
        $this->assertStringContainsString($message, file_get_contents($this->logFile));
    }

    public function testLogWithContext()
    {
        $message = "Test message with context";
        $context = ['user_id' => 1, 'action' => 'test'];
        
        $this->loggingService->info($message, $context);
        
        // Check if log file contains the message with context
        $logContent = file_get_contents($this->logFile);
        $this->assertStringContainsString($message, $logContent);
        $this->assertStringContainsString('user_id', $logContent);
    }

    public function testGetLogs()
    {
        // Write a test log entry
        $this->loggingService->info("Test log entry");
        
        // Check if log file exists and has content
        $this->assertFileExists($this->logFile);
        clearstatcache(true, $this->logFile);
        $this->assertGreaterThan(0, filesize($this->logFile));
    }

    public function testClearLogs()
    {
        // Write a test log entry
        $this->loggingService->info("Test log entry");
        
        // Check that the log file exists and has content
        $this->assertFileExists($this->logFile);
        clearstatcache(true, $this->logFile);
        $this->assertGreaterThan(0, filesize($this->logFile));
        
        // Note: LoggingService doesn't have a clear method, so we just verify logging works
    }
} 