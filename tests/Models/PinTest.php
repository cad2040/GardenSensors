<?php
namespace GardenSensors\Tests\Models;

use PHPUnit\Framework\TestCase;
use GardenSensors\Models\Pin;
use GardenSensors\Core\Database;
use GardenSensors\Models\Sensor;

class PinTest extends TestCase {
    private $pin;
    private $sensor;

    protected function setUp(): void {
        parent::setUp();
        
        // Create a test sensor
        $this->sensor = new Sensor([
            'sensor' => 'Test Sensor',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'status' => Sensor::STATUS_ACTIVE
        ]);
        $this->sensor->save();

        // Create a test pin
        $this->pin = new Pin([
            'sensor_id' => $this->sensor->id,
            'pin' => 'A0',
            'pinType' => Pin::TYPE_SENSOR,
            'description' => 'Test Pin',
            'status' => Pin::STATUS_ACTIVE
        ]);
        $this->pin->save();
    }

    protected function tearDown(): void {
        // Clean up test data
        $this->pin->delete();
        $this->sensor->delete();
        
        parent::tearDown();
    }

    public function testPinCreation() {
        $this->assertNotNull($this->pin->id);
        $this->assertEquals('A0', $this->pin->pin);
        $this->assertEquals(Pin::TYPE_SENSOR, $this->pin->pinType);
        $this->assertEquals(Pin::STATUS_ACTIVE, $this->pin->status);
    }

    public function testPinSensorRelationship() {
        $sensor = $this->pin->sensor();
        $this->assertNotNull($sensor);
        $this->assertEquals($this->sensor->id, $sensor->id);
    }

    public function testPinStatusManagement() {
        $this->assertTrue($this->pin->isActive());
        
        $this->pin->updateStatus(Pin::STATUS_INACTIVE);
        $this->assertTrue($this->pin->isInactive());
        
        $this->pin->updateStatus(Pin::STATUS_FAULTY);
        $this->assertTrue($this->pin->isFaulty());
    }

    public function testPinTypes() {
        $types = Pin::getTypes();
        $this->assertContains(Pin::TYPE_PUMP, $types);
        $this->assertContains(Pin::TYPE_SENSOR, $types);
        $this->assertContains(Pin::TYPE_RELAY, $types);
    }

    public function testPinStatuses() {
        $statuses = Pin::getStatuses();
        $this->assertContains(Pin::STATUS_ACTIVE, $statuses);
        $this->assertContains(Pin::STATUS_INACTIVE, $statuses);
        $this->assertContains(Pin::STATUS_FAULTY, $statuses);
    }

    public function testFindBySensor() {
        $pins = Pin::findBySensor($this->sensor->id);
        $this->assertCount(1, $pins);
        $this->assertEquals($this->pin->id, $pins[0]['id']);
    }

    public function testFindByPin() {
        $pin = Pin::findByPin('A0');
        $this->assertNotNull($pin);
        $this->assertEquals($this->pin->id, $pin['id']);
    }

    public function testPinDeletion() {
        $pinId = $this->pin->id;
        $this->pin->delete();
        
        $pin = Pin::findByPin('A0');
        $this->assertNull($pin);
    }

    public function testPinTimestamps() {
        $this->assertNotNull($this->pin->inserted);
        $this->assertNotNull($this->pin->updated);
    }
} 