<?php
/**
 * シンプルログクラス
 */
class SimpleLogger {
    private $logFile;
    private $debugMode;
    
    public function __construct() {
        $this->debugMode = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $logDir = dirname(__DIR__, 2) . '/logs';
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $this->logFile = $logDir . '/inventory.log';
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        if (!$this->debugMode && $level === 'INFO') {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
?>