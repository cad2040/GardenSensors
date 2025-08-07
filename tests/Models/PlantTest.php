<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\Plant;
use GardenSensors\Models\Sensor;

class PlantTest extends TestCase
{
    private $plant;
    private $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->db->exec("
            INSERT INTO users (username, email, password_hash, role, status, created_at, updated_at)
            VALUES ('testuser', 'test@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'user', 'active', NOW(), NOW())
        ");
        
        // Create test sensor
        $this->db->exec("
            INSERT INTO sensors (name, type, description, location, status, created_at, updated_at)
            VALUES ('Soil Moisture Sensor', 'moisture', 'Test sensor', 'Garden Bed 1', 'active', NOW(), NOW())
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
            'name' => 'Test Plant',
            'species' => 'Test Species',
            'description' => 'Test Description',
            'location' => 'Garden Bed 1',
            'planting_date' => date('Y-m-d'),
            'harvest_date' => null,
            'status' => 'active',
            'user_id' => 1
        ]);
    }

    public function testPlantCreation()
    {
        $this->plant->save();
        
        $this->assertNotNull($this->plant->getId());
        $this->assertEquals('Test Plant', $this->plant->getName());
        $this->assertEquals('Test Species', $this->plant->getSpecies());
        $this->assertEquals('Test Description', $this->plant->getDescription());
        $this->assertEquals('Garden Bed 1', $this->plant->getLocation());
        $this->assertEquals(date('Y-m-d'), $this->plant->getPlantingDate());
        $this->assertNull($this->plant->getHarvestDate());
        $this->assertEquals('active', $this->plant->getStatus());
        $this->assertEquals(1, $this->plant->getUserId());
    }

    public function testPlantUpdate()
    {
        $this->plant->save();
        
        $this->plant->setName('Updated Plant');
        $this->plant->setDescription('Updated Description');
        $this->plant->save();
        
        $updated = Plant::find($this->plant->getId());
        $this->assertEquals('Updated Plant', $updated->getName());
        $this->assertEquals('Updated Description', $updated->getDescription());
    }

    public function testPlantDeletion()
    {
        $this->plant->save();
        $id = $this->plant->getId();
        
        $this->plant->delete();
        
        $deleted = Plant::find($id);
        $this->assertNull($deleted);
    }

    public function testPlantSensors()
    {
        $this->plant->save();
        $this->sensor->save();
        
        // Associate sensor with plant
        $this->db->exec("
            INSERT INTO fact_plants (plant_id, sensor_id, created_at, updated_at)
            VALUES ({$this->plant->getId()}, {$this->sensor->getId()}, NOW(), NOW())
        ");
        
        $sensors = $this->plant->getSensors();
        $this->assertCount(1, $sensors);
        $this->assertEquals('Soil Moisture Sensor', $sensors[0]->getName());
    }

    public function testPlantFindByUser()
    {
        $this->plant->save();
        
        $plants = Plant::findByUser(1);
        $this->assertCount(1, $plants);
        $this->assertEquals('Test Plant', $plants[0]->getName());
    }

    public function testPlantFindByLocation()
    {
        $this->plant->save();
        
        $plants = Plant::findByLocation('Garden Bed 1');
        $this->assertCount(1, $plants);
        $this->assertEquals('Test Plant', $plants[0]->getName());
    }

    public function testPlantFindByStatus()
    {
        $this->plant->save();
        
        $plants = Plant::findByStatus('active');
        $this->assertCount(1, $plants);
        $this->assertEquals('Test Plant', $plants[0]->getName());
    }

    public function testPlantFindByDateRange()
    {
        $this->plant->save();
        
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('+1 day'));
        
        $plants = Plant::findByDateRange($startDate, $endDate);
        $this->assertCount(1, $plants);
        $this->assertEquals('Test Plant', $plants[0]->getName());
    }

    public function testPlantHarvest()
    {
        $this->plant->save();
        
        $this->plant->harvest();
        
        $harvested = Plant::find($this->plant->getId());
        $this->assertEquals('harvested', $harvested->getStatus());
        $this->assertEquals(date('Y-m-d'), $harvested->getHarvestDate());
    }

    public function testPlantValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Plant([
            'name' => '',
            'species' => 'Test Species',
            'description' => 'Test Description',
            'location' => 'Garden Bed 1',
            'planting_date' => date('Y-m-d'),
            'status' => 'active',
            'user_id' => 1
        ]);
    }
} 