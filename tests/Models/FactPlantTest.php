<?php
namespace GardenSensors\Tests\Models;

use PHPUnit\Framework\TestCase;
use GardenSensors\Models\FactPlant;
use GardenSensors\Core\Database;
use GardenSensors\Models\Plant;
use GardenSensors\Models\Sensor;

class FactPlantTest extends TestCase {
    private $factPlant;
    private $plant;
    private $sensor;

    protected function setUp(): void {
        parent::setUp();
        
        // Create a test plant
        $this->plant = new Plant([
            'plant' => 'Test Plant',
            'species' => 'Test Species',
            'minSoilMoisture' => 30,
            'maxSoilMoisture' => 70,
            'wateringFrequency' => 24
        ]);
        $this->plant->save();

        // Create a test sensor
        $this->sensor = new Sensor([
            'sensor' => 'Test Sensor',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'status' => Sensor::STATUS_ACTIVE
        ]);
        $this->sensor->save();

        // Create a test fact plant
        $this->factPlant = new FactPlant([
            'sensor_id' => $this->sensor->id,
            'plant_id' => $this->plant->id,
            'waterAmount' => 0
        ]);
        $this->factPlant->save();
    }

    protected function tearDown(): void {
        // Clean up test data
        $this->factPlant->delete();
        $this->sensor->delete();
        $this->plant->delete();
        
        parent::tearDown();
    }

    public function testFactPlantCreation() {
        $this->assertNotNull($this->factPlant->id);
        $this->assertEquals($this->sensor->id, $this->factPlant->sensor_id);
        $this->assertEquals($this->plant->id, $this->factPlant->plant_id);
        $this->assertEquals(0, $this->factPlant->waterAmount);
    }

    public function testFactPlantRelationships() {
        $sensor = $this->factPlant->sensor();
        $plant = $this->factPlant->plant();
        
        $this->assertNotNull($sensor);
        $this->assertNotNull($plant);
        $this->assertEquals($this->sensor->id, $sensor->id);
        $this->assertEquals($this->plant->id, $plant->id);
    }

    public function testUpdateWatering() {
        $this->factPlant->updateWatering();
        
        $this->assertNotNull($this->factPlant->lastWatered);
        $this->assertNotNull($this->factPlant->nextWatering);
        
        $nextWatering = strtotime($this->factPlant->nextWatering);
        $expectedNextWatering = strtotime("+{$this->plant->wateringFrequency} hours");
        
        $this->assertEquals($expectedNextWatering, $nextWatering, '', 60); // Allow 1 minute difference
    }

    public function testNeedsWatering() {
        // Initially should not need watering
        $this->assertFalse($this->factPlant->needsWatering());
        
        // Set next watering to past time
        $this->factPlant->nextWatering = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->factPlant->save();
        
        $this->assertTrue($this->factPlant->needsWatering());
    }

    public function testUpdateWaterAmount() {
        $amount = 100;
        $this->factPlant->updateWaterAmount($amount);
        
        $this->assertEquals($amount, $this->factPlant->waterAmount);
    }

    public function testFindBySensor() {
        $factPlants = FactPlant::findBySensor($this->sensor->id);
        $this->assertCount(1, $factPlants);
        $this->assertEquals($this->factPlant->id, $factPlants[0]['id']);
    }

    public function testFindByPlant() {
        $factPlants = FactPlant::findByPlant($this->plant->id);
        $this->assertCount(1, $factPlants);
        $this->assertEquals($this->factPlant->id, $factPlants[0]['id']);
    }

    public function testGetPlantsNeedingWater() {
        // Set next watering to past time
        $this->factPlant->nextWatering = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->factPlant->save();
        
        $plants = FactPlant::getPlantsNeedingWater();
        $this->assertCount(1, $plants);
        $this->assertEquals($this->plant->plant, $plants[0]['plant']);
        $this->assertEquals($this->plant->species, $plants[0]['species']);
    }

    public function testFactPlantDeletion() {
        $factPlantId = $this->factPlant->id;
        $this->factPlant->delete();
        
        $factPlants = FactPlant::findBySensor($this->sensor->id);
        $this->assertCount(0, $factPlants);
    }

    public function testFactPlantTimestamps() {
        $this->assertNotNull($this->factPlant->inserted);
        $this->assertNotNull($this->factPlant->updated);
    }
} 