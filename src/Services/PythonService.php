<?php
namespace App\Services;

use App\Core\AppConfig;
use App\Exceptions\PythonExecutionException;

class PythonService {
    private $pythonPath;
    private $projectRoot;
    private $db;
    private $ftp;
    private $pump;
    private $plotter;

    public function __construct() {
        $this->pythonPath = AppConfig::get('python.path', '/usr/bin/python3');
        $this->projectRoot = dirname(dirname(dirname(__DIR__)));
    }

    /**
     * Execute a Python script with arguments
     *
     * @param string $script Path to the Python script
     * @param array $args Arguments to pass to the script
     * @return array Output from the Python script
     * @throws PythonExecutionException
     */
    private function executePythonScript(string $script, array $args = []): array {
        $scriptPath = $this->projectRoot . '/python/' . $script;
        if (!file_exists($scriptPath)) {
            throw new PythonExecutionException("Python script not found: {$script}");
        }

        $command = escapeshellcmd($this->pythonPath) . ' ' . escapeshellarg($scriptPath);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new PythonExecutionException(
                "Python script execution failed with code {$returnVar}: " . implode("\n", $output)
            );
        }

        return $output;
    }

    /**
     * Generate a plot for sensor data
     *
     * @param string $sensorId Sensor ID
     * @param string $startDate Start date for data range
     * @param string $endDate End date for data range
     * @param string $outputPath Path to save the plot
     * @return bool Success status
     */
    public function generatePlot(string $sensorId, string $startDate, string $endDate, string $outputPath): bool {
        try {
            $this->executePythonScript('ProducePlot.py', [
                '--sensor-id', $sensorId,
                '--start-date', $startDate,
                '--end-date', $endDate,
                '--output', $outputPath
            ]);
            return true;
        } catch (PythonExecutionException $e) {
            error_log("Failed to generate plot: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Control the pump
     *
     * @param int $pin GPIO pin number
     * @param int $duration Duration in seconds
     * @return bool Success status
     */
    public function controlPump(int $pin, int $duration): bool {
        try {
            $this->executePythonScript('RunPump.py', [
                '--pin', $pin,
                '--duration', $duration
            ]);
            return true;
        } catch (PythonExecutionException $e) {
            error_log("Failed to control pump: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload a file via FTP
     *
     * @param string $localPath Local file path
     * @param string $remotePath Remote file path
     * @return bool Success status
     */
    public function uploadFile(string $localPath, string $remotePath): bool {
        try {
            $this->executePythonScript('FTPConnectMod.py', [
                '--action', 'upload',
                '--local', $localPath,
                '--remote', $remotePath
            ]);
            return true;
        } catch (PythonExecutionException $e) {
            error_log("Failed to upload file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Download a file via FTP
     *
     * @param string $remotePath Remote file path
     * @param string $localPath Local file path
     * @return bool Success status
     */
    public function downloadFile(string $remotePath, string $localPath): bool {
        try {
            $this->executePythonScript('FTPConnectMod.py', [
                '--action', 'download',
                '--remote', $remotePath,
                '--local', $localPath
            ]);
            return true;
        } catch (PythonExecutionException $e) {
            error_log("Failed to download file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a database query using Python
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array Query results
     * @throws PythonExecutionException
     */
    public function executeQuery(string $query, array $params = []): array {
        try {
            $output = $this->executePythonScript('DBConnect.py', [
                '--query', $query,
                '--params', json_encode($params)
            ]);
            return json_decode($output[0], true) ?? [];
        } catch (PythonExecutionException $e) {
            error_log("Failed to execute query: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if Python environment is properly configured
     *
     * @return bool Configuration status
     */
    public function checkEnvironment(): bool {
        try {
            $this->executePythonScript('check_env.py');
            return true;
        } catch (PythonExecutionException $e) {
            error_log("Python environment check failed: " . $e->getMessage());
            return false;
        }
    }
} 