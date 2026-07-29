<?php
namespace GardenSensors\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * CLI smoke checks for the catalog importer that do not need MySQL.
 */
class ImportPlantCatalogCliTest extends TestCase
{
    public function testImportScriptExistsAndShowsHelp(): void
    {
        $script = dirname(__DIR__, 2) . '/database/import_plant_catalog.php';
        $this->assertFileIsReadable($script);

        $cmd = sprintf('php %s --help 2>&1', escapeshellarg($script));
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
        $this->assertStringContainsString('--file', implode("\n", $output));
    }

    public function testImportScriptSkipsMissingFileWithoutRequireFlag(): void
    {
        $script = dirname(__DIR__, 2) . '/database/import_plant_catalog.php';
        $missing = sys_get_temp_dir() . '/missing_plant_catalog_' . uniqid('', true) . '.csv';

        $cmd = sprintf(
            'php %s --file=%s 2>&1',
            escapeshellarg($script),
            escapeshellarg($missing)
        );
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
        $this->assertStringContainsString('skipping', strtolower(implode("\n", $output)));
    }

    public function testImportScriptRequireFileFailsWhenMissing(): void
    {
        $script = dirname(__DIR__, 2) . '/database/import_plant_catalog.php';
        $missing = sys_get_temp_dir() . '/missing_required_catalog_' . uniqid('', true) . '.csv';

        $cmd = sprintf(
            'php %s --file=%s --require-file 2>&1',
            escapeshellarg($script),
            escapeshellarg($missing)
        );
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        $this->assertSame(1, $code, implode("\n", $output));
    }

    public function testExampleCsvHasRequiredHeader(): void
    {
        $example = dirname(__DIR__, 2) . '/database/catalog/plant_catalog.csv.example';
        $this->assertFileIsReadable($example);
        $header = trim((string) fgets(fopen($example, 'r')));
        $this->assertStringContainsString('common_name', $header);
        $this->assertStringContainsString('external_pid', $header);
        $this->assertStringContainsString('min_soil_moisture', $header);
        $this->assertStringContainsString('source', $header);
    }
}
