<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Plant;
use GardenSensors\Database;
use GardenSensors\Cache;
use GardenSensors\Logger;
use PHPUnit\Framework\TestCase;
use Mockery;

class PlantTest extends TestCase
{
    private $plant;
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
        
        // Create plant instance with mocked dependencies
        $this->plant = new Plant(
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

    public function testAddPlant()
    {
        $plantData = [
            'name' => 'Test Plant',
            'species' => 'Tomato',
            'location' => 'Garden',
            'min_moisture' => 40,
            'max_moisture' => 80,
            'min_temperature' => 15,
            'max_temperature' => 30,
            'status' => 'active'
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "INSERT INTO plants (name, species, location, min_moisture, max_moisture, min_temperature, max_temperature, status, user_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $plantData['name'],
                    $plantData['species'],
                    $plantData['location'],
                    $plantData['min_moisture'],
                    $plantData['max_moisture'],
                    $plantData['min_temperature'],
                    $plantData['max_temperature'],
                    $plantData['status'],
                    1
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('plants:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Plant added', ['plant_id' => null, 'user_id' => 1]);

        $result = $this->plant->add($plantData);
        $this->assertTrue($result);
    }

    public function testGetPlant()
    {
        $plantId = 1;
        $expectedData = [
            'id' => $plantId,
            'name' => 'Test Plant',
            'species' => 'Tomato',
            'location' => 'Garden',
            'min_moisture' => 40,
            'max_moisture' => 80,
            'min_temperature' => 15,
            'max_temperature' => 30,
            'status' => 'active'
        ];

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("plant:{$plantId}")
            ->andReturn(null);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT * FROM plants WHERE id = ? AND user_id = ?",
                [$plantId, 1]
            )
            ->andReturn($expectedData);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("plant:{$plantId}", $expectedData, 3600);

        $result = $this->plant->get($plantId);
        $this->assertEquals($expectedData, $result);
    }

    public function testUpdatePlant()
    {
        $plantId = 1;
        $updateData = [
            'name' => 'Updated Plant',
            'min_moisture' => 50
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "UPDATE plants SET name = ?, min_moisture = ?, updated_at = NOW() WHERE id = ? AND user_id = ?",
                [
                    $updateData['name'],
                    $updateData['min_moisture'],
                    $plantId,
                    1
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with("plant:{$plantId}");

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Plant updated', ['plant_id' => $plantId, 'user_id' => 1]);

        $result = $this->plant->update($plantId, $updateData);
        $this->assertTrue($result);
    }

    public function testDeletePlant()
    {
        $plantId = 1;

        // Check for associated sensors
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT COUNT(*) as count FROM sensors WHERE plant_id = ? AND user_id = ?",
                [$plantId, 1]
            )
            ->andReturn(['count' => 0]);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "DELETE FROM plants WHERE id = ? AND user_id = ?",
                [$plantId, 1]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with("plant:{$plantId}");

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Plant deleted', ['plant_id' => $plantId, 'user_id' => 1]);

        $result = $this->plant->delete($plantId);
        $this->assertTrue($result);
    }

    public function testGetPlantSensors()
    {
        $plantId = 1;
        $expectedSensors = [
            ['id' => 1, 'plant_id' => $plantId, 'name' => 'Sensor 1', 'type' => 'moisture'],
            ['id' => 2, 'plant_id' => $plantId, 'name' => 'Sensor 2', 'type' => 'temperature']
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT * FROM sensors WHERE plant_id = ? AND user_id = ?",
                [$plantId, 1]
            )
            ->andReturn($expectedSensors);

        $result = $this->plant->getSensors($plantId);
        $this->assertEquals($expectedSensors, $result);
    }
} 