<?php
namespace GardenSensors\Tests\Database;

use GardenSensors\Tests\TestCase;

/**
 * Exercises database/import_plant_catalog.php against a temporary CSV.
 */
class ImportPlantCatalogTest extends TestCase
{
    private $csvPath;
    private $importedIds = [];
    private $unique;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database unavailable: ' . $e->getMessage());
        }
        $this->unique = uniqid('imp_', true);
        $this->csvPath = null;
        $this->importedIds = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->importedIds as $id) {
            try {
                $this->db->exec('DELETE FROM plant_catalog WHERE id = ?', [$id]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        if ($this->csvPath && is_file($this->csvPath)) {
            @unlink($this->csvPath);
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

    public function testImportScriptUpsertsCsvRows(): void
    {
        $this->requireCatalogTable();

        $script = dirname(__DIR__, 2) . '/database/import_plant_catalog.php';
        if (!is_readable($script)) {
            $this->markTestSkipped('import_plant_catalog.php not found');
        }
        if (!is_readable(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
            $this->markTestSkipped('Composer vendor/autoload.php required for import script');
        }

        $common = 'Imported Plant ' . $this->unique;
        $pid = $this->unique . '_ext';
        $this->csvPath = sys_get_temp_dir() . '/plant_catalog_' . $this->unique . '.csv';

        $csv = implode(',', [
            'external_pid', 'common_name', 'scientific_name', 'category', 'description',
            'min_soil_moisture', 'max_soil_moisture', 'min_temperature', 'max_temperature',
            'min_soil_ec', 'max_soil_ec', 'min_env_humidity', 'max_env_humidity',
            'preferred_ph_min', 'preferred_ph_max', 'water_requirement', 'nutrient_level',
            'sunlight', 'watering_frequency_hours', 'source', 'source_url', 'is_active',
        ]) . "\n";
        $csv .= implode(',', [
            $pid,
            '"' . $common . '"',
            '"Ocimum basilicum"',
            'herb',
            '"Imported for unit test"',
            36, 72, 14.0, 29.0,
            '', '', '', '',
            '', '', 'medium', '',
            'full', 21, 'manual', '', 1,
        ]) . "\n";

        file_put_contents($this->csvPath, $csv);

        $cmd = sprintf(
            'php %s --file=%s 2>&1',
            escapeshellarg($script),
            escapeshellarg($this->csvPath)
        );
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
        $this->assertStringContainsString('1', implode("\n", $output));

        $rows = $this->db->query(
            'SELECT id, common_name, scientific_name, min_soil_moisture, max_soil_moisture, watering_frequency_hours, source, external_pid
             FROM plant_catalog WHERE external_pid = ? AND source = ?',
            [$pid, 'manual']
        );
        $this->assertCount(1, $rows);
        $this->assertSame($common, $rows[0]['common_name']);
        $this->assertSame('Ocimum basilicum', $rows[0]['scientific_name']);
        $this->assertSame(36, (int) $rows[0]['min_soil_moisture']);
        $this->assertSame(72, (int) $rows[0]['max_soil_moisture']);
        $this->assertSame(21, (int) $rows[0]['watering_frequency_hours']);
        $this->importedIds[] = (int) $rows[0]['id'];

        // Upsert: change moisture and re-import
        $csv2 = implode(',', [
            'external_pid', 'common_name', 'scientific_name', 'category', 'description',
            'min_soil_moisture', 'max_soil_moisture', 'min_temperature', 'max_temperature',
            'min_soil_ec', 'max_soil_ec', 'min_env_humidity', 'max_env_humidity',
            'preferred_ph_min', 'preferred_ph_max', 'water_requirement', 'nutrient_level',
            'sunlight', 'watering_frequency_hours', 'source', 'source_url', 'is_active',
        ]) . "\n";
        $csv2 .= implode(',', [
            $pid,
            '"' . $common . '"',
            '"Ocimum basilicum"',
            'herb',
            '"Updated"',
            40, 80, 14.0, 29.0,
            '', '', '', '',
            '', '', 'high', '',
            'full', 12, 'manual', '', 1,
        ]) . "\n";
        file_put_contents($this->csvPath, $csv2);

        $output2 = [];
        $code2 = 0;
        exec($cmd, $output2, $code2);
        $this->assertSame(0, $code2, implode("\n", $output2));

        $rows = $this->db->query(
            'SELECT id, min_soil_moisture, max_soil_moisture, watering_frequency_hours, description
             FROM plant_catalog WHERE external_pid = ? AND source = ?',
            [$pid, 'manual']
        );
        $this->assertCount(1, $rows);
        $this->assertSame(40, (int) $rows[0]['min_soil_moisture']);
        $this->assertSame(80, (int) $rows[0]['max_soil_moisture']);
        $this->assertSame(12, (int) $rows[0]['watering_frequency_hours']);
        $this->importedIds[] = (int) $rows[0]['id'];
    }

    public function testImportScriptDryRunDoesNotPersist(): void
    {
        $this->requireCatalogTable();

        $script = dirname(__DIR__, 2) . '/database/import_plant_catalog.php';
        if (!is_readable($script) || !is_readable(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
            $this->markTestSkipped('import script or vendor missing');
        }

        $common = 'DryRun Plant ' . $this->unique;
        $pid = $this->unique . '_dry';
        $this->csvPath = sys_get_temp_dir() . '/plant_catalog_dry_' . $this->unique . '.csv';
        file_put_contents($this->csvPath, "external_pid,common_name,source,is_active\n{$pid},\"{$common}\",manual,1\n");

        $cmd = sprintf(
            'php %s --file=%s --dry-run 2>&1',
            escapeshellarg($script),
            escapeshellarg($this->csvPath)
        );
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
        $this->assertStringContainsString('dry-run', strtolower(implode("\n", $output)));

        $rows = $this->db->query(
            'SELECT id FROM plant_catalog WHERE external_pid = ? AND source = ?',
            [$pid, 'manual']
        );
        $this->assertSame([], $rows);
    }
}
