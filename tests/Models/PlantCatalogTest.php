<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\Plant;
use GardenSensors\Models\PlantCatalog;

/**
 * Database-backed plant catalog tests (requires garden_sensors + plant_catalog table).
 */
class PlantCatalogTest extends TestCase
{
    /** @var int[] */
    private $catalogIds = [];

    /** @var int[] */
    private $plantIds = [];

    private $unique;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database unavailable: ' . $e->getMessage());
        }
        $this->unique = uniqid('cat_', true);
        $this->catalogIds = [];
        $this->plantIds = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->plantIds as $id) {
            try {
                $this->db->exec('DELETE FROM plants WHERE id = ?', [$id]);
            } catch (\Throwable $e) {
                // ignore cleanup failures
            }
        }
        foreach ($this->catalogIds as $id) {
            try {
                $this->db->exec('DELETE FROM plant_catalog WHERE id = ?', [$id]);
            } catch (\Throwable $e) {
                // ignore cleanup failures
            }
        }
        parent::tearDown();
    }

    private function requireCatalogTable(): void
    {
        try {
            $this->db->query('SELECT 1 FROM plant_catalog LIMIT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('plant_catalog table not available: ' . $e->getMessage());
        }
    }

    private function insertCatalog(array $overrides = []): int
    {
        $this->requireCatalogTable();

        $row = array_merge([
            'external_pid' => $this->unique . '_pid',
            'common_name' => 'Catalog Plant ' . $this->unique,
            'scientific_name' => 'Testus plantus',
            'category' => 'herb',
            'description' => 'Unit test catalog entry',
            'min_soil_moisture' => 35,
            'max_soil_moisture' => 75,
            'min_temperature' => 12.5,
            'max_temperature' => 28.0,
            'water_requirement' => 'medium',
            'watering_frequency_hours' => 20,
            'source' => 'manual',
            'is_active' => 1,
        ], $overrides);

        $this->db->exec("
            INSERT INTO plant_catalog (
                external_pid, common_name, scientific_name, category, description,
                min_soil_moisture, max_soil_moisture, min_temperature, max_temperature,
                water_requirement, watering_frequency_hours, source, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $row['external_pid'],
            $row['common_name'],
            $row['scientific_name'],
            $row['category'],
            $row['description'],
            $row['min_soil_moisture'],
            $row['max_soil_moisture'],
            $row['min_temperature'],
            $row['max_temperature'],
            $row['water_requirement'],
            $row['watering_frequency_hours'],
            $row['source'],
            $row['is_active'],
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->catalogIds[] = $id;
        return $id;
    }

    public function testListForDropdownIncludesActiveAndSupportsSearch(): void
    {
        $activeName = 'Active Searchable ' . $this->unique;
        $inactiveName = 'Inactive Hidden ' . $this->unique;

        $activeId = $this->insertCatalog([
            'external_pid' => $this->unique . '_active',
            'common_name' => $activeName,
            'scientific_name' => 'Searchus activus',
            'category' => 'veg',
            'is_active' => 1,
        ]);
        $this->insertCatalog([
            'external_pid' => $this->unique . '_inactive',
            'common_name' => $inactiveName,
            'is_active' => 0,
        ]);

        $all = PlantCatalog::listForDropdown($this->unique, 50);
        $names = array_column($all, 'common_name');
        $this->assertContains($activeName, $names);
        $this->assertNotContains($inactiveName, $names);

        $bySci = PlantCatalog::listForDropdown('Searchus activus', 50);
        $ids = array_map('intval', array_column($bySci, 'id'));
        $this->assertContains($activeId, $ids);

        $empty = PlantCatalog::listForDropdown('zzz-no-match-' . $this->unique, 50);
        $this->assertSame([], $empty);
    }

    public function testCountActiveExcludesInactive(): void
    {
        $this->requireCatalogTable();
        $before = PlantCatalog::countActive();
        $this->insertCatalog([
            'external_pid' => $this->unique . '_cnt_a',
            'common_name' => 'Count Active ' . $this->unique,
            'is_active' => 1,
        ]);
        $this->insertCatalog([
            'external_pid' => $this->unique . '_cnt_i',
            'common_name' => 'Count Inactive ' . $this->unique,
            'is_active' => 0,
        ]);

        $this->assertSame($before + 1, PlantCatalog::countActive());
    }

    public function testFindAndListLimitClamp(): void
    {
        $id = $this->insertCatalog([
            'external_pid' => $this->unique . '_find',
            'common_name' => 'Findable ' . $this->unique,
        ]);

        $found = PlantCatalog::find($id);
        $this->assertInstanceOf(PlantCatalog::class, $found);
        $serialized = $found->jsonSerialize();
        $this->assertSame('Findable ' . $this->unique, $serialized['common_name']);
        $this->assertSame($id, (int) $serialized['id']);

        $rows = PlantCatalog::listForDropdown($this->unique, 0);
        $this->assertLessThanOrEqual(1, count($rows));
        $rows = PlantCatalog::listForDropdown(null, 9999);
        $this->assertIsArray($rows);
    }

    public function testPlantAttributesFromCatalogAndRelation(): void
    {
        $this->requireCatalogTable();

        try {
            $this->db->query('SELECT catalog_id FROM plants LIMIT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('plants.catalog_id not available: ' . $e->getMessage());
        }

        $catalogId = $this->insertCatalog([
            'external_pid' => $this->unique . '_link',
            'common_name' => 'Linked Catalog ' . $this->unique,
            'scientific_name' => 'Linkus plantus',
            'min_soil_moisture' => 41,
            'max_soil_moisture' => 79,
            'watering_frequency_hours' => 22,
            'min_temperature' => 10.0,
            'max_temperature' => 30.0,
        ]);

        $catalog = PlantCatalog::find($catalogId);
        $this->assertNotNull($catalog);

        $fromInstance = $catalog->toPlantAttributes('Instance Plant ' . $this->unique);
        $this->assertSame($catalogId, $fromInstance['catalog_id']);
        $this->assertSame(22, $fromInstance['watering_frequency']);

        $attrs = Plant::attributesFromCatalog($catalog, 'My Linked Plant ' . $this->unique);
        $this->assertSame($catalogId, $attrs['catalog_id']);
        $this->assertSame(41, $attrs['min_soil_moisture']);
        $this->assertSame(79, $attrs['max_soil_moisture']);
        $this->assertSame(22, $attrs['watering_frequency']);

        $serialized = $catalog->jsonSerialize();
        $this->assertArrayHasKey('common_name', $serialized);
        $this->assertArrayNotHasKey('created_at', $serialized);

        $plant = new Plant(array_merge($attrs, [
            'user_id' => 1,
            'location' => 'Test Bed',
        ]));
        $this->assertTrue($plant->save());
        $plantId = $plant->getId();
        $this->assertNotNull($plantId);
        $this->plantIds[] = (int) $plantId;

        $loaded = Plant::find($plantId);
        $this->assertNotNull($loaded);
        $related = $loaded->catalog();
        $this->assertInstanceOf(PlantCatalog::class, $related);
        $this->assertSame($catalogId, (int) ($related->jsonSerialize()['id'] ?? 0));

        $orphan = new Plant([
            'name' => 'No Catalog ' . $this->unique,
            'min_soil_moisture' => 30,
            'max_soil_moisture' => 70,
            'watering_frequency' => 24,
            'status' => 'active',
        ]);
        $this->assertNull($orphan->catalog());
    }
}
