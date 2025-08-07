<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\Pin;
use GardenSensors\Models\Sensor;

class PinTest extends TestCase
{
    private $pin;
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
        
        $this->pin = new Pin([
            'pin_number' => 17,
            'type' => 'digital',
            'mode' => 'output',
            'sensor_id' => 1,
            'status' => 'active'
        ]);
    }

    public function testPinCreation()
    {
        $this->pin->save();
        
        $this->assertNotNull($this->pin->getId());
        $this->assertEquals(17, $this->pin->getPinNumber());
        $this->assertEquals('digital', $this->pin->getType());
        $this->assertEquals('output', $this->pin->getMode());
        $this->assertEquals(1, $this->pin->getSensorId());
        $this->assertEquals('active', $this->pin->getStatus());
    }

    public function testPinUpdate()
    {
        $this->pin->save();
        
        $this->pin->setMode('input');
        $this->pin->setStatus('inactive');
        $this->pin->save();
        
        $updated = Pin::find($this->pin->getId());
        $this->assertEquals('input', $updated->getMode());
        $this->assertEquals('inactive', $updated->getStatus());
    }

    public function testPinDeletion()
    {
        $this->pin->save();
        $id = $this->pin->getId();
        
        $this->pin->delete();
        
        $deleted = Pin::find($id);
        $this->assertNull($deleted);
    }

    public function testPinSensorRelationship()
    {
        $this->pin->save();
        
        $sensor = $this->pin->getSensor();
        $this->assertNotNull($sensor);
        $this->assertEquals('Soil Moisture Sensor', $sensor->getName());
    }

    public function testPinFindBySensor()
    {
        $this->pin->save();
        
        $pins = Pin::findBySensor(1);
        $this->assertCount(1, $pins);
        $this->assertEquals(17, $pins[0]->getPinNumber());
    }

    public function testPinFindByType()
    {
        $this->pin->save();
        
        $pins = Pin::findByType('digital');
        $this->assertCount(1, $pins);
        $this->assertEquals(17, $pins[0]->getPinNumber());
    }

    public function testPinFindByMode()
    {
        $this->pin->save();
        
        $pins = Pin::findByMode('output');
        $this->assertCount(1, $pins);
        $this->assertEquals(17, $pins[0]->getPinNumber());
    }

    public function testPinFindByStatus()
    {
        $this->pin->save();
        
        $pins = Pin::findByStatus('active');
        $this->assertCount(1, $pins);
        $this->assertEquals(17, $pins[0]->getPinNumber());
    }

    public function testPinValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Pin([
            'pin_number' => -1,
            'type' => 'invalid',
            'mode' => 'invalid',
            'sensor_id' => 1,
            'status' => 'active'
        ]);
    }
} 