<?php
namespace App\Utils;

class Logger {
    private static $instance = null;
    private $config = [];
    private $logFile;

    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600
    ];

    private function __construct() {
        $this->loadConfig();
        $this->setupLogFile();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): void {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    private function setupLogFile(): void {
        $this->logFile = $this->config['log']['path'];
        $logDir = dirname($this->logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0644);
        }
    }

    private function shouldLog(string $level): bool {
        $configLevel = strtolower($this->config['log']['level']);
        return self::LEVELS[$level] >= self::LEVELS[$configLevel];
    }

    private function write(string $level, string $message, array $context = []): void {
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            $contextStr
        );

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    public static function debug(string $message, array $context = []): void {
        self::getInstance()->write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void {
        self::getInstance()->write('info', $message, $context);
    }

    public static function notice(string $message, array $context = []): void {
        self::getInstance()->write('notice', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::getInstance()->write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::getInstance()->write('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void {
        self::getInstance()->write('critical', $message, $context);
    }

    public static function alert(string $message, array $context = []): void {
        self::getInstance()->write('alert', $message, $context);
    }

    public static function emergency(string $message, array $context = []): void {
        self::getInstance()->write('emergency', $message, $context);
    }

    public function getLogFile(): string {
        return $this->logFile;
    }

    public function clearLog(): bool {
        return file_put_contents($this->logFile, '') !== false;
    }

    public function getLastEntries(int $lines = 100): array {
        $entries = [];
        $file = new \SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $start = max(0, $lastLine - $lines);
        $file->seek($start);

        while (!$file->eof()) {
            $line = $file->current();
            if (!empty(trim($line))) {
                $entries[] = $line;
            }
            $file->next();
        }

        return $entries;
    }
} 