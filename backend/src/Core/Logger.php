<?php
namespace App\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Level;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface{
    private MonologLogger $logger;
    private array $logFiles = [];

    public function __construct() {
        $this->logger = new MonologLogger('app');
        $this->setupLogFiles();
    }

    private function setupLogFiles(): void {
        $this->logFiles = [
            Level::Error->value => LOG_DIR . '/error.log',
            Level::Info->value => LOG_DIR . '/info.log',
            Level::Debug->value => LOG_DIR . '/debug.log',
        ];
    }

    private function writeLog(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logLine = "[$timestamp] $level: $message $contextStr\n";

        $file = match($level) {
            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => $this->logFiles[Level::Error->value],
            default => $this->logFiles[Level::Info->value],
        };
        file_put_contents($file, $logLine, FILE_APPEND);

        if(APP_ENV === "dev") {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, $logLine);
            fclose($stderr);
        }
    }

    public function emergency(string|\Stringable $message, array $context = []): void {
        $this->writeLog('EMERGENCY', (string)$message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void {
        $this->writeLog('ALERT', (string)$message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void {
        $this->writeLog('CRITICAL', (string)$message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void {
        $this->writeLog('ERROR', (string)$message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void {
        $this->writeLog('WARNING', (string)$message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void {
        $this->writeLog('NOTICE', (string)$message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void {
        $this->writeLog('INFO', (string)$message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void {
        $this->writeLog('DEBUG', (string)$message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void {
        $this->writeLog(strtoupper($level), (string)$message, $context);
    }

}
