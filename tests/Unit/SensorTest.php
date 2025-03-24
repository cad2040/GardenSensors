<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Sensor;
use GardenSensors\Database;
use GardenSensors\Cache;
use GardenSensors\Logger;
use PHPUnit\Framework\TestCase;
use Mockery;

class SensorTest extends TestCase
{
    private $sensor;
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
        
        // Create sensor instance with mocked dependencies
        $this->sensor = new Sensor(
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

    public function testAddSensor()
    {
        $sensorData = [
            'name' => 'Test Sensor',
            'type' => 'moisture',
            'location' => 'Garden',
            'plant_id' => 1,
            'battery_level' => 100,
            'last_reading' => null,
            'status' => 'active'
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "INSERT INTO sensors (name, type, location, plant_id, battery_level, last_reading, status, user_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $sensorData['name'],
                    $sensorData['type'],
                    $sensorData['location'],
                    $sensorData['plant_id'],
                    $sensorData['battery_level'],
                    $sensorData['last_reading'],
                    $sensorData['status'],
                    1
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('sensors:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Sensor added', ['sensor_id' => null, 'user_id' => 1]);

        $result = $this->sensor->add($sensorData);
        $this->assertTrue($result);
    }

    public function testGetSensor()
    {
        $sensorId = 1;
        $expectedData = [
            'id' => $sensorId,
            'name' => 'Test Sensor',
            'type' => 'moisture',
            'location' => 'Garden',
            'plant_id' => 1,
            'battery_level' => 100,
            'last_reading' => null,
            'status' => 'active'
        ];

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("sensor:{$sensorId}")
            ->andReturn(null);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT * FROM sensors WHERE id = ? AND user_id = ?",
                [$sensorId, 1]
            )
            ->andReturn($expectedData);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("sensor:{$sensorId}", $expectedData, 3600);

        $result = $this->sensor->get($sensorId);
        $this->assertEquals($expectedData, $result);
    }

    public function testUpdateSensor()
    {
        $sensorId = 1;
        $updateData = [
            'name' => 'Updated Sensor',
            'battery_level' => 90
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "UPDATE sensors SET name = ?, battery_level = ?, updated_at = NOW() WHERE id = ? AND user_id = ?",
                [
                    $updateData['name'],
                    $updateData['battery_level'],
                    $sensorId,
                    1
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with("sensor:{$sensorId}");

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Sensor updated', ['sensor_id' => $sensorId, 'user_id' => 1]);

        $result = $this->sensor->update($sensorId, $updateData);
        $this->assertTrue($result);
    }

    public function testDeleteSensor()
    {
        $sensorId = 1;

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "DELETE FROM sensors WHERE id = ? AND user_id = ?",
                [$sensorId, 1]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with("sensor:{$sensorId}");

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Sensor deleted', ['sensor_id' => $sensorId, 'user_id' => 1]);

        $result = $this->sensor->delete($sensorId);
        $this->assertTrue($result);
    }

    public function testGetSensorReadings()
    {
        $sensorId = 1;
        $expectedReadings = [
            ['id' => 1, 'sensor_id' => $sensorId, 'value' => 75, 'timestamp' => '2024-01-01 12:00:00'],
            ['id' => 2, 'sensor_id' => $sensorId, 'value' => 80, 'timestamp' => '2024-01-01 13:00:00']
        ];

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

        $result = $this->sensor->getReadings($sensorId);
        $this->assertEquals($expectedReadings, $result);
    }
} 