<?php
namespace GardenSensors\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GardenSensors\Models\PlantCatalog;

/**
 * Pure unit tests for catalog → plant mapping helpers (no database).
 */
class PlantCatalogMappingTest extends TestCase
{
    public function testWateringFrequencyFromRequirement(): void
    {
        $this->assertSame(48, PlantCatalog::wateringFrequencyFromRequirement('low'));
        $this->assertSame(48, PlantCatalog::wateringFrequencyFromRequirement('Drought'));
        $this->assertSame(48, PlantCatalog::wateringFrequencyFromRequirement(' sparse '));
        $this->assertSame(12, PlantCatalog::wateringFrequencyFromRequirement('high'));
        $this->assertSame(12, PlantCatalog::wateringFrequencyFromRequirement('FREQUENT'));
        $this->assertSame(12, PlantCatalog::wateringFrequencyFromRequirement('wet'));
        $this->assertSame(24, PlantCatalog::wateringFrequencyFromRequirement('medium'));
        $this->assertSame(24, PlantCatalog::wateringFrequencyFromRequirement('moderate'));
        $this->assertSame(24, PlantCatalog::wateringFrequencyFromRequirement(null));
        $this->assertSame(24, PlantCatalog::wateringFrequencyFromRequirement(''));
        $this->assertSame(24, PlantCatalog::wateringFrequencyFromRequirement('unknown'));
    }

    public function testMapRowToPlantAttributesUsesCatalogThresholds(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'id' => 42,
            'common_name' => 'Tomato',
            'scientific_name' => 'Solanum lycopersicum',
            'description' => 'Warm season crop',
            'min_soil_moisture' => 40,
            'max_soil_moisture' => 80,
            'min_temperature' => 15.0,
            'max_temperature' => 32.0,
            'watering_frequency_hours' => 18,
            'water_requirement' => 'high',
        ], 'Patio Tomato');

        $this->assertSame('Patio Tomato', $attrs['name']);
        $this->assertSame('Solanum lycopersicum', $attrs['species']);
        $this->assertSame('Warm season crop', $attrs['description']);
        $this->assertSame(40, $attrs['min_soil_moisture']);
        $this->assertSame(80, $attrs['max_soil_moisture']);
        $this->assertSame(18, $attrs['watering_frequency']);
        $this->assertSame(15.0, $attrs['min_temperature']);
        $this->assertSame(32.0, $attrs['max_temperature']);
        $this->assertSame(42, $attrs['catalog_id']);
        $this->assertSame('active', $attrs['status']);
    }

    public function testMapRowFallsBackToWaterRequirementWhenHoursMissing(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'common_name' => 'Succulent',
            'water_requirement' => 'low',
        ]);

        $this->assertSame('Succulent', $attrs['name']);
        $this->assertSame(48, $attrs['watering_frequency']);
        $this->assertSame(30, $attrs['min_soil_moisture']);
        $this->assertSame(70, $attrs['max_soil_moisture']);
        $this->assertNull($attrs['catalog_id']);
    }

    public function testMapRowCorrectsInvertedMoistureRange(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'common_name' => 'Broken Range',
            'min_soil_moisture' => 80,
            'max_soil_moisture' => 40,
        ]);

        $this->assertSame(80, $attrs['min_soil_moisture']);
        $this->assertSame(90, $attrs['max_soil_moisture']);
    }

    public function testMapRowCapsCorrectedMoistureAt100(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'common_name' => 'Edge',
            'min_soil_moisture' => 95,
            'max_soil_moisture' => 90,
        ]);

        $this->assertSame(95, $attrs['min_soil_moisture']);
        $this->assertSame(100, $attrs['max_soil_moisture']);
    }

    public function testMapRowUsesSpeciesFallbackAndDefaultName(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'species' => 'Fallback species',
        ]);

        $this->assertSame('Plant', $attrs['name']);
        $this->assertSame('Fallback species', $attrs['species']);
        $this->assertNull($attrs['min_temperature']);
        $this->assertNull($attrs['max_temperature']);
    }

    public function testMapRowIgnoresEmptyDisplayName(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'common_name' => 'Basil',
        ], '');

        $this->assertSame('Basil', $attrs['name']);
    }

    public function testMapRowTreatsEmptyStringHoursAsMissing(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'common_name' => 'Herbs',
            'watering_frequency_hours' => '',
            'water_requirement' => 'high',
        ]);

        $this->assertSame(12, $attrs['watering_frequency']);
    }

    public function testMapRowZeroFrequencyFallsBackToDefault(): void
    {
        $attrs = PlantCatalog::mapRowToPlantAttributes([
            'common_name' => 'Odd',
            'watering_frequency_hours' => 0,
        ]);

        $this->assertSame(24, $attrs['watering_frequency']);
    }
}
