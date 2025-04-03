<?php
class Logger {
    private $logFile;
    private $logLevel;
    private $maxSize;
    private $maxFiles;

    public function __construct() {
        $this->logFile = LOG_FILE;
        $this->logLevel = LOG_LEVEL;
        $this->maxSize = LOG_MAX_SIZE;
        $this->maxFiles = LOG_MAX_FILES;

        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }

    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }

    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }

    private function log($level, $message, $context = []) {
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

    private function shouldLog($level) {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3
        ];

        return $levels[$level] >= $levels[$this->logLevel];
    }

    private function rotateLogs() {
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