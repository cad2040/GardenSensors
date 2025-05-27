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
        
        // Create test user
        $this->db->exec("
            INSERT INTO users (username, email, password, role, status, created_at, updated_at)
            VALUES ('testuser', 'test@example.com', 'password', 'user', 'active', NOW(), NOW())
        ");
        
        // Create test sensor
        $this->db->exec("
            INSERT INTO sensors (name, type, description, location, status, min_threshold, max_threshold, unit, user_id, created_at, updated_at)
            VALUES ('Soil Moisture Sensor', 'moisture', 'Test sensor', 'Garden Bed 1', 'active', 20, 80, '%', 1, NOW(), NOW())
        ");
        
        // Create test plant
        $this->db->exec("
            INSERT INTO dim_plants (name, species, description, location, planting_date, harvest_date, status, user_id, created_at, updated_at)
            VALUES ('Test Plant', 'Test Species', 'Test Description', 'Garden Bed 1', NOW(), NULL, 'active', 1, NOW(), NOW())
        ");
        
        $this->sensor = new Sensor([
            'id' => 1,
            'name' => 'Soil Moisture Sensor',
            'type' => 'moisture',
            'description' => 'Test sensor',
            'location' => 'Garden Bed 1',
            'status' => 'active',
            'min_threshold' => 20,
            'max_threshold' => 80,
            'unit' => '%',
            'user_id' => 1
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
            INSERT INTO sensors (name, type, description, location, status, min_threshold, max_threshold, unit, user_id, created_at, updated_at)
            VALUES ('Temperature Sensor', 'temperature', 'Test sensor 2', 'Garden Bed 1', 'active', 15, 30, '°C', 1, NOW(), NOW())
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
        $this->assertEquals('Test Plant', $plant->getName());
        
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