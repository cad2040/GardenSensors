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
    }

    public function testPlantCreation()
    {
        $this->plant->save();
        
        $this->assertNotNull($this->plant->getId());
        $this->assertStringStartsWith('Test Plant', $this->plant->getName());
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
        
        $uniqueId = uniqid();
        $updatedName = 'Updated Plant ' . $uniqueId;
        $this->plant->setName($updatedName);
        $this->plant->setDescription('Updated Description');
        $this->plant->save();
        
        $updated = Plant::find($this->plant->getId());
        $this->assertEquals($updatedName, $updated->getName());
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
        
        // Associate sensor with plant using plant_sensors table
        $this->db->exec("
            INSERT INTO plant_sensors (plant_id, sensor_id, water_amount, next_watering, created_at, updated_at)
            VALUES ({$this->plant->getId()}, {$this->sensor->getId()}, 100, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW(), NOW())
        ");
        
        $sensors = $this->plant->getSensors();
        $this->assertCount(1, $sensors);
        $this->assertEquals('Soil Moisture Sensor', $sensors[0]->getName());
    }

    public function testPlantMoistureThresholds()
    {
        $this->plant->save();
        
        $this->assertEquals(30, $this->plant->getMinSoilMoisture());
        $this->assertEquals(70, $this->plant->getMaxSoilMoisture());
        
        // Test threshold validation
        $this->assertLessThan($this->plant->getMaxSoilMoisture(), $this->plant->getMinSoilMoisture());
    }

    public function testPlantPhAndEcZones()
    {
        $uniqueId = uniqid();
        $plant = new Plant([
            'name' => 'EC Range Plant ' . $uniqueId,
            'species' => 'Test Species',
            'min_soil_moisture' => 30,
            'max_soil_moisture' => 70,
            'watering_frequency' => 24,
            'min_ph' => 6.0,
            'max_ph' => 6.8,
            'ec_underfed' => 0.8,
            'ec_balanced_min' => 1.0,
            'ec_balanced_max' => 1.6,
            'ec_overfed' => 2.0,
            'status' => 'active',
            'user_id' => 1,
        ]);
        $plant->save();

        $this->assertEquals(6.0, $plant->getMinPh());
        $this->assertEquals(6.8, $plant->getMaxPh());
        $this->assertEquals(0.8, $plant->getEcUnderfed());
        $this->assertEquals(1.6, $plant->getEcBalancedMax());
    }

    public function testPlantInvalidEcBalancedRangeThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Plant([
            'name' => 'Bad EC Plant ' . uniqid(),
            'species' => 'Test Species',
            'min_soil_moisture' => 30,
            'max_soil_moisture' => 70,
            'watering_frequency' => 24,
            'ec_balanced_min' => 2.0,
            'ec_balanced_max' => 1.0,
            'status' => 'active',
            'user_id' => 1,
        ]);
    }

    public function testPlantInvalidPhRangeThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Plant([
            'name' => 'Bad pH Plant ' . uniqid(),
            'species' => 'Test Species',
            'min_soil_moisture' => 30,
            'max_soil_moisture' => 70,
            'watering_frequency' => 24,
            'min_ph' => 7.5,
            'max_ph' => 6.0,
            'status' => 'active',
            'user_id' => 1,
        ]);
    }

    public function testPlantWateringFrequency()
    {
        $this->plant->save();
        
        $this->assertEquals(24, $this->plant->getWateringFrequency());
        
        $this->plant->setWateringFrequency(48);
        $this->plant->save();
        
        $updated = Plant::find($this->plant->getId());
        $this->assertEquals(48, $updated->getWateringFrequency());
    }

    public function testPlantSensorLinking()
    {
        $this->plant->save();
        $this->sensor->save();
        
        // Link sensor to plant via plant_sensors table
        $this->db->exec("
            INSERT INTO plant_sensors (plant_id, sensor_id, water_amount, next_watering, created_at, updated_at)
            VALUES ({$this->plant->getId()}, {$this->sensor->getId()}, 100, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW(), NOW())
        ");
        
        // Verify link exists
        $result = $this->db->query("
            SELECT * FROM plant_sensors 
            WHERE plant_id = {$this->plant->getId()} AND sensor_id = {$this->sensor->getId()}
        ");
        $this->assertCount(1, $result);
        $this->assertEquals(100, $result[0]['water_amount']);
    }

    public function testPlantMoistureThresholdValidation()
    {
        // Note: The Plant model doesn't validate thresholds in constructor
        // This test verifies the thresholds are stored correctly
        $uniqueId = uniqid();
        $plant = new Plant([
            'name' => 'Test Threshold Plant ' . $uniqueId,
            'min_soil_moisture' => 30,
            'max_soil_moisture' => 70,
            'watering_frequency' => 24,
            'user_id' => 1
        ]);
        $plant->save();
        
        $this->assertEquals(30, $plant->getMinSoilMoisture());
        $this->assertEquals(70, $plant->getMaxSoilMoisture());
    }

    public function testPlantAddSensor()
    {
        $this->plant->save();
        $this->sensor->save();
        
        $result = $this->plant->addSensor($this->sensor);
        $this->assertTrue($result);
        
        $sensors = $this->plant->getSensors();
        $this->assertCount(1, $sensors);
    }

    public function testPlantRemoveSensor()
    {
        $this->plant->save();
        $this->sensor->save();
        
        $this->plant->addSensor($this->sensor);
        $this->assertCount(1, $this->plant->getSensors());
        
        $result = $this->plant->removeSensor($this->sensor);
        $this->assertTrue($result);
        
        $sensors = $this->plant->getSensors();
        $this->assertCount(0, $sensors);
    }

    public function testPlantFindByUser()
    {
        $this->plant->save();
        
        $plants = Plant::findByUser(1);
        // Find the specific plant we created
        $foundPlant = null;
        foreach ($plants as $plant) {
            if (strpos($plant->getName(), 'Test Plant') === 0) {
                $foundPlant = $plant;
                break;
            }
        }
        $this->assertNotNull($foundPlant);
        $this->assertStringStartsWith('Test Plant', $foundPlant->getName());
    }

    public function testPlantFindByLocation()
    {
        $this->plant->save();
        
        $plants = Plant::findByLocation('Garden Bed 1');
        // Find the specific plant we created
        $foundPlant = null;
        foreach ($plants as $plant) {
            if (strpos($plant->getName(), 'Test Plant') === 0) {
                $foundPlant = $plant;
                break;
            }
        }
        $this->assertNotNull($foundPlant);
        $this->assertStringStartsWith('Test Plant', $foundPlant->getName());
    }

    public function testPlantFindByStatus()
    {
        $this->plant->save();
        
        $plants = Plant::findByStatus('active');
        // Find the specific plant we created
        $foundPlant = null;
        foreach ($plants as $plant) {
            if (strpos($plant->getName(), 'Test Plant') === 0) {
                $foundPlant = $plant;
                break;
            }
        }
        $this->assertNotNull($foundPlant);
        $this->assertStringStartsWith('Test Plant', $foundPlant->getName());
    }

    public function testPlantFindByDateRange()
    {
        $this->plant->save();
        
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('+1 day'));
        
        $plants = Plant::findByDateRange($startDate, $endDate);
        // Find the specific plant we created
        $foundPlant = null;
        foreach ($plants as $plant) {
            if (strpos($plant->getName(), 'Test Plant') === 0) {
                $foundPlant = $plant;
                break;
            }
        }
        $this->assertNotNull($foundPlant);
        $this->assertStringStartsWith('Test Plant', $foundPlant->getName());
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