<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\FactPlant;
use GardenSensors\Models\Plant;
use GardenSensors\Models\Sensor;

class FactPlantTest extends TestCase {
    private $factPlant;
    private $plant;
    private $sensor;

    protected function setUp(): void {
        parent::setUp();
        
        // Create test user with unique username
        $uniqueId = uniqid();
        $this->db->exec("
            INSERT INTO users (username, email, password_hash, role, status, created_at, updated_at)
            VALUES ('testuser_{$uniqueId}', 'test_{$uniqueId}@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'user', 'active', NOW(), NOW())
        ");
        
        // Create test sensor
        $this->db->exec("
            INSERT INTO sensors (name, type, description, location, status, created_at, updated_at)
            VALUES ('Soil Moisture Sensor', 'moisture', 'Test sensor', 'Garden Bed 1', 'active', NOW(), NOW())
        ");
        
        // Create test plant with unique name
        $uniqueId = uniqid();
        $this->db->exec("
            INSERT INTO plants (name, species, min_soil_moisture, max_soil_moisture, watering_frequency, location, status, user_id, created_at, updated_at)
            VALUES ('Test Plant {$uniqueId}', 'Test Species', 20, 80, 24, 'Garden Bed 1', 'active', 1, NOW(), NOW())
        ");
        
        $this->sensor = new Sensor([
            'id' => 1,
            'name' => 'Soil Moisture Sensor',
            'type' => 'moisture',
            'description' => 'Test sensor',
            'location' => 'Garden Bed 1',
            'status' => 'active'
        ]);
        
        $this->plant = new Plant([
            'id' => 1,
            'name' => 'Test Plant',
            'species' => 'Test Species',
            'description' => 'Test Description',
            'location' => 'Garden Bed 1',
            'planting_date' => date('Y-m-d'),
            'harvest_date' => null,
            'status' => 'active',
            'user_id' => 1
        ]);
        
        $this->factPlant = new FactPlant([
            'plant_id' => 1,
            'sensor_id' => 1
        ]);
    }

    public function testFactPlantCreation() {
        $this->factPlant->save();
        
        $this->assertNotNull($this->factPlant->getId());
        $this->assertEquals(1, $this->factPlant->getPlantId());
        $this->assertEquals(1, $this->factPlant->getSensorId());
    }

    public function testFactPlantUpdate() {
        $this->factPlant->save();
        
        // Create another sensor
        $this->db->exec("
            INSERT INTO sensors (name, type, description, location, status, created_at, updated_at)
            VALUES ('Temperature Sensor', 'temperature', 'Test sensor 2', 'Garden Bed 1', 'active', NOW(), NOW())
        ");
        
        $this->factPlant->setSensorId(2);
        $this->factPlant->save();
        
        $updated = FactPlant::find($this->factPlant->getId());
        $this->assertEquals(2, $updated->getSensorId());
    }

    public function testFactPlantDeletion() {
        $this->factPlant->save();
        $id = $this->factPlant->getId();
        
        $this->factPlant->delete();
        
        $deleted = FactPlant::find($id);
        $this->assertNull($deleted);
    }

    public function testFactPlantRelationships() {
        $this->factPlant->save();
        
        $plant = $this->factPlant->getPlant();
        $this->assertNotNull($plant);
        $this->assertStringStartsWith('Test Plant', $plant->getName());
        
        $sensor = $this->factPlant->getSensor();
        $this->assertNotNull($sensor);
        $this->assertEquals('Soil Moisture Sensor', $sensor->getName());
    }

    public function testFactPlantFindByPlant() {
        $this->factPlant->save();
        
        $factPlants = FactPlant::findByPlant(1);
        $this->assertCount(1, $factPlants);
        $this->assertEquals(1, $factPlants[0]->getSensorId());
    }

    public function testFactPlantFindBySensor() {
        $this->factPlant->save();
        
        $factPlants = FactPlant::findBySensor(1);
        $this->assertCount(1, $factPlants);
        $this->assertEquals(1, $factPlants[0]->getPlantId());
    }

    public function testFactPlantValidation() {
        $this->expectException(\InvalidArgumentException::class);
        
        new FactPlant([
            'plant_id' => 999999,  // Non-existent plant
            'sensor_id' => 1
        ]);
    }
} 