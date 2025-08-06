<?php

namespace GardenSensors\Services;

class LoggingService {
    private string $logFile;
    private string $logLevel;
    private int $maxSize;
    private int $maxFiles;

    public function __construct() {
        $this->logFile = getenv('LOG_FILE') ?: '/tmp/garden_sensors.log';
        $this->logLevel = getenv('LOG_LEVEL') ?: 'debug';
        $this->maxSize = (int)(getenv('LOG_MAX_SIZE') ?: 10485760); // 10MB
        $this->maxFiles = (int)(getenv('LOG_MAX_FILES') ?: 5);

        // Ensure log directory is writable
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Create log file if it doesn't exist and make it writable
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0666);
        }
    }

    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void {
        if (!$this->shouldLog($level)) {
            return;
        }

        $this->rotateLogs();

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $logMessage .= " " . json_encode($context);
        }
        
        $logMessage .= PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    private function shouldLog(string $level): bool {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3
        ];

        return $levels[$level] >= $levels[$this->logLevel];
    }

    private function rotateLogs(): void {
        if (!file_exists($this->logFile)) {
            return;
        }

        if (filesize($this->logFile) >= $this->maxSize) {
            $info = pathinfo($this->logFile);
            $rotatedFile = $info['dirname'] . '/' . $info['filename'] . '_' . date('Y-m-d_H-i-s') . '.' . $info['extension'];
            
            rename($this->logFile, $rotatedFile);
            
            // Clean up old log files
            $files = glob($info['dirname'] . '/' . $info['filename'] . '_*.' . $info['extension']);
            rsort($files);
            
            foreach (array_slice($files, $this->maxFiles) as $file) {
                unlink($file);
            }
        }
    }
} 