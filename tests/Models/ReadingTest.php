<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Reading;
use App\Models\Sensor;

class ReadingTest extends TestCase
{
    private $reading;
    private $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test sensor
        $this->sensor = new Sensor([
            'sensor' => 'Test Sensor',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'status' => Sensor::STATUS_ACTIVE
        ]);
        $this->sensor->save();

        // Create a test reading
        $this->reading = new Reading([
            'sensor_id' => $this->sensor->id,
            'reading' => 45.5,
            'temperature' => 22.3,
            'humidity' => 65.0
        ]);
        $this->reading->save();
    }

    protected function tearDown(): void
    {
        $this->reading->delete();
        $this->sensor->delete();
        parent::tearDown();
    }

    public function testReadingCreation()
    {
        $this->assertNotNull($this->reading->id);
        $this->assertEquals($this->sensor->id, $this->reading->sensor_id);
        $this->assertEquals(45.5, $this->reading->reading);
        $this->assertEquals(22.3, $this->reading->temperature);
        $this->assertEquals(65.0, $this->reading->humidity);
    }

    public function testReadingSensorRelationship()
    {
        $sensor = $this->reading->sensor();
        $this->assertNotNull($sensor);
        $this->assertEquals($this->sensor->id, $sensor->id);
    }

    public function testReadingTimestamps()
    {
        $this->assertNotNull($this->reading->inserted);
        $this->assertNotNull($this->reading->updated);
    }

    public function testReadingValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Reading([
            'sensor_id' => 999999,  // Non-existent sensor
            'reading' => 'invalid',  // Invalid reading value
            'temperature' => 'invalid',  // Invalid temperature
            'humidity' => 'invalid'  // Invalid humidity
        ]);
    }

    public function testReadingFindBySensor()
    {
        $readings = Reading::findBySensor($this->sensor->id);
        $this->assertCount(1, $readings);
        $this->assertEquals($this->reading->id, $readings[0]->id);
    }

    public function testReadingFindByDateRange()
    {
        $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $endDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $readings = Reading::findByDateRange($this->sensor->id, $startDate, $endDate);
        $this->assertCount(1, $readings);
        $this->assertEquals($this->reading->id, $readings[0]->id);
    }

    public function testReadingAverage()
    {
        // Create additional readings
        $reading2 = new Reading([
            'sensor_id' => $this->sensor->id,
            'reading' => 55.5,
            'temperature' => 23.3,
            'humidity' => 70.0
        ]);
        $reading2->save();

        $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $endDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $average = Reading::getAverage($this->sensor->id, $startDate, $endDate);
        $this->assertEquals(50.5, $average, '', 0.1); // Allow 0.1 difference
    }

    public function testReadingDeletion()
    {
        $readingId = $this->reading->id;
        $this->reading->delete();
        
        $deleted = Reading::find($readingId);
        $this->assertNull($deleted);
    }

    public function testReadingBatchInsert()
    {
        $readings = [
            [
                'sensor_id' => $this->sensor->id,
                'reading' => 60.0,
                'temperature' => 24.0,
                'humidity' => 75.0
            ],
            [
                'sensor_id' => $this->sensor->id,
                'reading' => 65.0,
                'temperature' => 25.0,
                'humidity' => 80.0
            ]
        ];
        
        Reading::batchInsert($readings);
        
        $allReadings = Reading::findBySensor($this->sensor->id);
        $this->assertCount(3, $allReadings); // Including the one from setUp
    }

    public function testReadingCleanup()
    {
        // Create old reading
        $oldReading = new Reading([
            'sensor_id' => $this->sensor->id,
            'reading' => 50.0,
            'temperature' => 20.0,
            'humidity' => 60.0,
            'inserted' => date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
        $oldReading->save();
        
        // Clean up old readings
        Reading::cleanup(7); // Keep last 7 days
        
        $readings = Reading::findBySensor($this->sensor->id);
        $this->assertCount(1, $readings); // Only the one from setUp should remain
    }
} 