<?php
// logger.php

function logResult($message, $file) {
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
}

function logAll($message) {
    $logs = ["frontend.log", "dmz.log", "backend.log", "apache.log"];
    foreach ($logs as $logFile) {
        logResult($message, $logFile);
    }
}

function logError($message, $file = "error.log") {
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($file, "[$timestamp] ERROR: $message\n", FILE_APPEND);
}

function logAllErrors($message) {
    $logs = ["frontend.log", "dmz.log", "backend.log", "apache.log", "error.log"];
    foreach ($logs as $logFile) {
        logError($message, $logFile);
    }
}
