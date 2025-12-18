<?php
// logger.php

/**
 * Log general messages to a file
 */
function logAll($message) {
    $logFile = __DIR__ . '/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log error messages to a separate file
 */
function logAllErrors($message) {
    $logFile = __DIR__ . '/logs/errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>