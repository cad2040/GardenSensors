<?php
namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PythonService;
use App\Exceptions\PythonExecutionException;

class PythonServiceTest extends TestCase {
    private $pythonService;
    private $mockExecOutput = [];

    protected function setUp(): void {
        parent::setUp();
        $this->pythonService = new PythonService();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Mock the exec function for testing
     */
    private function mockExec($command, &$output, &$returnVar) {
        $output = $this->mockExecOutput;
        $returnVar = 0;
        return true;
    }

    public function testGeneratePlot() {
        $this->mockExecOutput = ['Plot generated successfully'];
        
        $result = $this->pythonService->generatePlot(
            'sensor1',
            '2024-01-01',
            '2024-01-02',
            'plots/sensor1.png'
        );

        $this->assertTrue($result);
    }

    public function testGeneratePlotFailure() {
        $this->mockExecOutput = ['Error: Failed to generate plot'];
        
        $result = $this->pythonService->generatePlot(
            'sensor1',
            '2024-01-01',
            '2024-01-02',
            'plots/sensor1.png'
        );

        $this->assertFalse($result);
    }

    public function testControlPump() {
        $this->mockExecOutput = ['Pump controlled successfully'];
        
        $result = $this->pythonService->controlPump(18, 5);

        $this->assertTrue($result);
    }

    public function testControlPumpFailure() {
        $this->mockExecOutput = ['Error: Failed to control pump'];
        
        $result = $this->pythonService->controlPump(18, 5);

        $this->assertFalse($result);
    }

    public function testUploadFile() {
        $this->mockExecOutput = ['File uploaded successfully'];
        
        $result = $this->pythonService->uploadFile(
            'local/file.txt',
            'remote/file.txt'
        );

        $this->assertTrue($result);
    }

    public function testUploadFileFailure() {
        $this->mockExecOutput = ['Error: Failed to upload file'];
        
        $result = $this->pythonService->uploadFile(
            'local/file.txt',
            'remote/file.txt'
        );

        $this->assertFalse($result);
    }

    public function testDownloadFile() {
        $this->mockExecOutput = ['File downloaded successfully'];
        
        $result = $this->pythonService->downloadFile(
            'remote/file.txt',
            'local/file.txt'
        );

        $this->assertTrue($result);
    }

    public function testDownloadFileFailure() {
        $this->mockExecOutput = ['Error: Failed to download file'];
        
        $result = $this->pythonService->downloadFile(
            'remote/file.txt',
            'local/file.txt'
        );

        $this->assertFalse($result);
    }

    public function testExecuteQuery() {
        $this->mockExecOutput = [json_encode(['result1', 'result2'])];
        
        $result = $this->pythonService->executeQuery(
            'SELECT * FROM test_table',
            ['param1']
        );

        $this->assertEquals(['result1', 'result2'], $result);
    }

    public function testExecuteQueryFailure() {
        $this->expectException(PythonExecutionException::class);
        
        $this->mockExecOutput = ['Error: Failed to execute query'];
        
        $this->pythonService->executeQuery('SELECT * FROM test_table');
    }

    public function testCheckEnvironment() {
        $this->mockExecOutput = ['Environment check passed'];
        
        $result = $this->pythonService->checkEnvironment();

        $this->assertTrue($result);
    }

    public function testCheckEnvironmentFailure() {
        $this->mockExecOutput = ['Error: Environment check failed'];
        
        $result = $this->pythonService->checkEnvironment();

        $this->assertFalse($result);
    }

    public function testExecutePythonScriptNotFound() {
        $this->expectException(PythonExecutionException::class);
        
        $this->pythonService->executePythonScript('nonexistent.py');
    }

    public function testExecutePythonScriptFailure() {
        $this->expectException(PythonExecutionException::class);
        
        $this->mockExecOutput = ['Error: Script execution failed'];
        
        $this->pythonService->executePythonScript('test.py');
    }
} 