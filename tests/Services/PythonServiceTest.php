<?php
namespace GardenSensors\Tests\Services;

use PHPUnit\Framework\TestCase;
use GardenSensors\Services\PythonService;
use GardenSensors\Exceptions\PythonExecutionException;
use GardenSensors\Config\AppConfig;

class PythonServiceTest extends TestCase {
    private $pythonService;
    private $mockExecOutput = [];
    private $mockExecReturnVar = 0;

    protected function setUp(): void {
        parent::setUp();
        
        // Set up AppConfig with test values
        AppConfig::set('python.path', '/home/cad2040/Code/GardenSensors/venv/bin/python3');
            
        $this->pythonService = $this->getMockBuilder(PythonService::class)
            ->onlyMethods(['executePythonScript'])
            ->getMock();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    public function testGeneratePlot() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->with(
                'ProducePlot.py',
                [
                    '--sensor-id', 'sensor1',
                    '--start-date', '2024-01-01',
                    '--end-date', '2024-01-02',
                    '--output', 'plots/sensor1.png'
                ]
            )
            ->willReturn(['Plot generated successfully']);
        
        $result = $this->pythonService->generatePlot(
            'sensor1',
            '2024-01-01',
            '2024-01-02',
            'plots/sensor1.png'
        );

        $this->assertTrue($result);
    }

    public function testGeneratePlotFailure() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->willThrowException(new PythonExecutionException('Failed to generate plot'));
        
        $result = $this->pythonService->generatePlot(
            'sensor1',
            '2024-01-01',
            '2024-01-02',
            'plots/sensor1.png'
        );

        $this->assertFalse($result);
    }

    public function testControlPump() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->with(
                'RunPump.py',
                [
                    '--pin', '18',
                    '--duration', '5'
                ]
            )
            ->willReturn(['Pump controlled successfully']);
        
        $result = $this->pythonService->controlPump(18, 5);

        $this->assertTrue($result);
    }

    public function testControlPumpFailure() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->willThrowException(new PythonExecutionException('Failed to control pump'));
        
        $result = $this->pythonService->controlPump(18, 5);

        $this->assertFalse($result);
    }

    public function testUploadFile() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->with(
                'FTPConnectMod.py',
                [
                    '--action', 'upload',
                    '--local', 'local/file.txt',
                    '--remote', 'remote/file.txt'
                ]
            )
            ->willReturn(['File uploaded successfully']);
        
        $result = $this->pythonService->uploadFile(
            'local/file.txt',
            'remote/file.txt'
        );

        $this->assertTrue($result);
    }

    public function testUploadFileFailure() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->willThrowException(new PythonExecutionException('Failed to upload file'));
        
        $result = $this->pythonService->uploadFile(
            'local/file.txt',
            'remote/file.txt'
        );

        $this->assertFalse($result);
    }

    public function testDownloadFile() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->with(
                'FTPConnectMod.py',
                [
                    '--action', 'download',
                    '--remote', 'remote/file.txt',
                    '--local', 'local/file.txt'
                ]
            )
            ->willReturn(['File downloaded successfully']);
        
        $result = $this->pythonService->downloadFile(
            'remote/file.txt',
            'local/file.txt'
        );

        $this->assertTrue($result);
    }

    public function testDownloadFileFailure() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->willThrowException(new PythonExecutionException('Failed to download file'));
        
        $result = $this->pythonService->downloadFile(
            'remote/file.txt',
            'local/file.txt'
        );

        $this->assertFalse($result);
    }

    public function testExecuteQuery() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->with(
                'DBConnect.py',
                [
                    '--query', 'SELECT * FROM test_table',
                    '--params', json_encode(['param1'])
                ]
            )
            ->willReturn(['{"results": ["result1", "result2"]}']);
        
        $result = $this->pythonService->executeQuery(
            'SELECT * FROM test_table',
            ['param1']
        );

        $this->assertEquals(['results' => ['result1', 'result2']], $result);
    }

    public function testExecuteQueryFailure() {
        $this->expectException(PythonExecutionException::class);
        
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->willThrowException(new PythonExecutionException('Failed to execute query'));
        
        $this->pythonService->executeQuery('SELECT * FROM test_table');
    }

    public function testCheckEnvironment() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->with('check_env.py')
            ->willReturn(['{"python_version": true, "required_packages": true}']);
        
        $result = $this->pythonService->checkEnvironment();

        $this->assertTrue($result);
    }

    public function testCheckEnvironmentFailure() {
        $this->pythonService->expects($this->once())
            ->method('executePythonScript')
            ->willThrowException(new PythonExecutionException('Environment check failed'));
        
        $result = $this->pythonService->checkEnvironment();

        $this->assertFalse($result);
    }

    public function testExecutePythonScriptNotFound() {
        $pythonService = new PythonService();
        $this->expectException(PythonExecutionException::class);
        $pythonService->executePythonScript('nonexistent.py');
    }

    public function testExecutePythonScriptFailure() {
        $pythonService = new PythonService();
        $this->expectException(PythonExecutionException::class);
        $pythonService->executePythonScript('test.py');
    }
} 