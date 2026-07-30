<?php

namespace GardenSensors\Tests\Services;

use GardenSensors\Tests\TestCase;

class DashboardSeedTest extends TestCase
{
    public function testEverySamplePlantHasDashboardMetricsAndReadings(): void
    {
        $rows = $this->db->query(
            "SELECT p.name, s.type, COUNT(r.id) AS reading_count
             FROM plants p
             JOIN plant_sensors ps ON ps.plant_id = p.id
             JOIN sensors s ON s.id = ps.sensor_id
             JOIN readings r ON r.sensor_id = s.id
             WHERE p.name IN ('Tomato Plant', 'Basil Plant', 'Lettuce Plant')
               AND s.type IN ('temperature', 'moisture', 'ec')
             GROUP BY p.name, s.type"
        );

        $coverage = [];
        foreach ($rows as $row) {
            $coverage[$row['name']][$row['type']] = (int)$row['reading_count'];
        }

        foreach (['Tomato Plant', 'Basil Plant', 'Lettuce Plant'] as $plant) {
            foreach (['temperature', 'moisture', 'ec'] as $metric) {
                $this->assertArrayHasKey($plant, $coverage);
                $this->assertArrayHasKey($metric, $coverage[$plant]);
                $this->assertGreaterThan(0, $coverage[$plant][$metric]);
            }
        }
    }

    public function testSamplePlantBenchmarksAreConfigured(): void
    {
        $plants = $this->db->query(
            "SELECT name, min_temperature, max_temperature,
                    min_soil_moisture, max_soil_moisture,
                    ec_underfed, ec_balanced_min, ec_balanced_max, ec_overfed
             FROM plants
             WHERE name IN ('Tomato Plant', 'Basil Plant', 'Lettuce Plant')"
        );

        $this->assertCount(3, $plants);
        foreach ($plants as $plant) {
            $this->assertLessThan((float)$plant['max_temperature'], (float)$plant['min_temperature']);
            $this->assertLessThan((float)$plant['max_soil_moisture'], (float)$plant['min_soil_moisture']);
            $this->assertSame(0.8, (float)$plant['ec_underfed']);
            $this->assertSame(1.0, (float)$plant['ec_balanced_min']);
            $this->assertSame(1.6, (float)$plant['ec_balanced_max']);
            $this->assertSame(2.0, (float)$plant['ec_overfed']);
        }
    }
}
