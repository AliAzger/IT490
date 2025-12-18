<?php
// logListener.php
require_once('rabbitMQLib.php');
require_once('logger.php');

// Corrected to use host.ini and errorQueue section
$server = new rabbitMQServer("host.ini", "errorQueue");
echo "RabbitMQ Log Listener started...\n";

$server->process_requests(function($request) {
    $msg = $request['message'] ?? "No message received";
    logAllErrors($msg); 
    echo "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n";
    return ["status" => "logged"];
});