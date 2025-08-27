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
        
        // Use unique pin number to avoid conflicts
        $uniquePinNumber = rand(1000, 9999);
        $this->pin = new Pin([
            'pin_number' => $uniquePinNumber,
            'pin' => 'D' . $uniquePinNumber,
            'pinType' => 'sensor',
            'pin_type' => 'sensor',
            'sensor_id' => 1,
            'status' => 'active'
        ]);
    }

    public function testPinCreation()
    {
        $this->pin->save();
        
        $this->assertNotNull($this->pin->getId());
        $this->assertNotNull($this->pin->getPinNumber());
        $this->assertNotNull($this->pin->getPin());
        $this->assertEquals('sensor', $this->pin->getPinType());
        $this->assertEquals(1, $this->pin->getSensorId());
        $this->assertEquals('active', $this->pin->getStatus());
    }

    public function testPinUpdate()
    {
        $this->pin->save();
        
        $this->pin->setPinType('relay');
        $this->pin->setStatus('inactive');
        $this->pin->save();
        
        $updated = Pin::find($this->pin->getId());
        $this->assertEquals('relay', $updated->getPinType());
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
        
        $pins = (new Pin())->findBySensor(1);
        $this->assertGreaterThan(0, count($pins));
        $foundPin = null;
        foreach ($pins as $pin) {
            if ($pin->getPinNumber() === $this->pin->getPinNumber()) {
                $foundPin = $pin;
                break;
            }
        }
        $this->assertNotNull($foundPin);
        $this->assertEquals($this->pin->getPinNumber(), $foundPin->getPinNumber());
    }

    public function testPinFindByType()
    {
        $this->pin->save();
        
        $pins = (new Pin())->where('pinType', '=', 'sensor');
        $this->assertGreaterThan(0, count($pins));
        $foundPin = null;
        foreach ($pins as $pin) {
            if ($pin->getPinNumber() === $this->pin->getPinNumber()) {
                $foundPin = $pin;
                break;
            }
        }
        $this->assertNotNull($foundPin);
        $this->assertEquals($this->pin->getPinNumber(), $foundPin->getPinNumber());
    }

    public function testPinFindByMode()
    {
        $this->pin->save();
        
        $pins = (new Pin())->where('pin_type', '=', 'sensor');
        $this->assertGreaterThan(0, count($pins));
        $foundPin = null;
        foreach ($pins as $pin) {
            if ($pin->getPinNumber() === $this->pin->getPinNumber()) {
                $foundPin = $pin;
                break;
            }
        }
        $this->assertNotNull($foundPin);
        $this->assertEquals($this->pin->getPinNumber(), $foundPin->getPinNumber());
    }

    public function testPinFindByStatus()
    {
        $this->pin->save();
        
        $pins = (new Pin())->where('status', '=', 'active');
        $this->assertGreaterThan(0, count($pins));
        $foundPin = null;
        foreach ($pins as $pin) {
            if ($pin->getPinNumber() === $this->pin->getPinNumber()) {
                $foundPin = $pin;
                break;
            }
        }
        $this->assertNotNull($foundPin);
        $this->assertEquals($this->pin->getPinNumber(), $foundPin->getPinNumber());
    }

    public function testPinValidation()
    {
        $this->markTestSkipped('Validation not implemented in Pin model');
    }
} 