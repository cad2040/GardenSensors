<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Reading;
use GardenSensors\Database;
use GardenSensors\Cache;
use GardenSensors\Logger;
use PHPUnit\Framework\TestCase;
use Mockery;

class ReadingTest extends TestCase
{
    private $reading;
    private $dbMock;
    private $cacheMock;
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->dbMock = Mockery::mock(Database::class);
        $this->cacheMock = Mockery::mock(Cache::class);
        $this->loggerMock = Mockery::mock(Logger::class);
        
        // Create reading instance with mocked dependencies
        $this->reading = new Reading(
            $this->dbMock,
            $this->cacheMock,
            $this->loggerMock,
            1 // test user ID
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAddReading()
    {
        $readingData = [
            'sensor_id' => 1,
            'value' => 75.5,
            'timestamp' => '2024-01-01 12:00:00'
        ];

        // Check if sensor exists and belongs to user
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT id FROM sensors WHERE id = ? AND user_id = ?",
                [$readingData['sensor_id'], 1]
            )
            ->andReturn(['id' => 1]);

        // Insert reading
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "INSERT INTO readings (sensor_id, value, timestamp) VALUES (?, ?, ?)",
                [
                    $readingData['sensor_id'],
                    $readingData['value'],
                    $readingData['timestamp']
                ]
            )
            ->andReturn(true);

        // Update sensor's last reading
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "UPDATE sensors SET last_reading = ? WHERE id = ?",
                [$readingData['timestamp'], $readingData['sensor_id']]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('readings:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Reading added', ['sensor_id' => $readingData['sensor_id']]);

        $result = $this->reading->add($readingData);
        $this->assertTrue($result);
    }

    public function testDeleteReading()
    {
        $readingId = 1;

        // Check if reading exists and belongs to user's sensor
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT r.id FROM readings r 
                JOIN sensors s ON r.sensor_id = s.id 
                WHERE r.id = ? AND s.user_id = ?",
                [$readingId, 1]
            )
            ->andReturn(['id' => 1]);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "DELETE FROM readings WHERE id = ?",
                [$readingId]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('readings:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Reading deleted', ['reading_id' => $readingId]);

        $result = $this->reading->delete($readingId);
        $this->assertTrue($result);
    }

    public function testGetReadings()
    {
        $sensorId = 1;
        $expectedReadings = [
            [
                'id' => 1,
                'sensor_id' => $sensorId,
                'value' => 75.5,
                'timestamp' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'sensor_id' => $sensorId,
                'value' => 80.0,
                'timestamp' => '2024-01-01 13:00:00'
            ]
        ];

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("readings:{$sensorId}")
            ->andReturn(null);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT r.* FROM readings r 
                JOIN sensors s ON r.sensor_id = s.id 
                WHERE s.id = ? AND s.user_id = ? 
                ORDER BY r.timestamp DESC",
                [$sensorId, 1]
            )
            ->andReturn($expectedReadings);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("readings:{$sensorId}", $expectedReadings, 300);

        $result = $this->reading->getReadings($sensorId);
        $this->assertEquals($expectedReadings, $result);
    }

    public function testExportReadings()
    {
        $sensorId = 1;
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        $format = 'csv';

        $readings = [
            [
                'id' => 1,
                'sensor_id' => $sensorId,
                'value' => 75.5,
                'timestamp' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'sensor_id' => $sensorId,
                'value' => 80.0,
                'timestamp' => '2024-01-01 13:00:00'
            ]
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT r.* FROM readings r 
                JOIN sensors s ON r.sensor_id = s.id 
                WHERE s.id = ? AND s.user_id = ? 
                AND r.timestamp BETWEEN ? AND ? 
                ORDER BY r.timestamp ASC",
                [$sensorId, 1, $startDate, $endDate]
            )
            ->andReturn($readings);

        $result = $this->reading->exportReadings($sensorId, $startDate, $endDate, $format);
        
        // Check if result is a string (CSV format)
        $this->assertIsString($result);
        
        // Check if CSV contains expected data
        $this->assertStringContainsString('75.5', $result);
        $this->assertStringContainsString('80.0', $result);
    }
} 