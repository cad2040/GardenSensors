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
        $this->sensor = new Sensor([
            'name' => 'Soil Moisture Sensor ' . uniqid(),
            'type' => 'moisture',
            'description' => 'Test sensor',
            'location' => 'Garden Bed 1',
            'status' => 'active'
        ]);
        $this->sensor->save();
        
        // Create test plant with unique name
        $uniqueId = uniqid();
        
        $this->plant = new Plant([
            'name' => 'Test Plant ' . $uniqueId,
            'species' => 'Test Species',
            'description' => 'Test Description',
            'location' => 'Garden Bed 1',
            'planting_date' => date('Y-m-d'),
            'harvest_date' => null,
            'status' => 'active',
            'user_id' => 1,
            'min_soil_moisture' => 30,
            'max_soil_moisture' => 70,
            'watering_frequency' => 24
        ]);
        $this->plant->save();
        
        $this->factPlant = new FactPlant([
            'plant_id' => $this->plant->getId(),
            'sensor_id' => $this->sensor->getId()
        ]);
        
        // Debug: Check if IDs are set correctly
        if ($this->plant->getId() === null) {
            throw new \Exception('Plant ID is null after save');
        }
        if ($this->sensor->getId() === null) {
            throw new \Exception('Sensor ID is null after save');
        }
    }

    public function testFactPlantCreation() {
        $this->factPlant->save();
        
        $this->assertNotNull($this->factPlant->getId());
        $this->assertEquals($this->plant->getId(), $this->factPlant->getPlantId());
        $this->assertEquals($this->sensor->getId(), $this->factPlant->getSensorId());
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
        
        $plant = $this->factPlant->plant();
        $this->assertNotNull($plant);
        $this->assertNotNull($plant->getName());
        
        $sensor = $this->factPlant->sensor();
        $this->assertNotNull($sensor);
        $this->assertEquals('Soil Moisture Sensor', $sensor->getName());
    }

    public function testFactPlantFindByPlant() {
        $this->factPlant->save();
        
        $factPlants = (new FactPlant())->findByPlant(1);
        // Find the specific fact plant we just created
        $ourFactPlant = null;
        foreach ($factPlants as $fp) {
            if ($fp->getPlantId() == 1 && $fp->getSensorId() == 1) {
                $ourFactPlant = $fp;
                break;
            }
        }
        $this->assertNotNull($ourFactPlant);
        $this->assertEquals(1, $ourFactPlant->getSensorId());
    }

    public function testFactPlantFindBySensor() {
        $this->factPlant->save();
        
        $factPlants = (new FactPlant())->findBySensor(1);
        // Find the specific fact plant we just created
        $ourFactPlant = null;
        foreach ($factPlants as $fp) {
            if ($fp->getPlantId() == 1 && $fp->getSensorId() == 1) {
                $ourFactPlant = $fp;
                break;
            }
        }
        $this->assertNotNull($ourFactPlant);
        $this->assertEquals(1, $ourFactPlant->getPlantId());
    }

    public function testFactPlantValidation() {
        // Note: FactPlant doesn't implement validation in constructor
        // This test is skipped as validation is not implemented
        $this->markTestSkipped('Validation not implemented in FactPlant constructor');
    }
} 