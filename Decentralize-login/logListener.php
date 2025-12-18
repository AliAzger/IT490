<?php
require_once('rabbitMQLib.inc');
require_once('logger.php');

$server = new rabbitMQServer("testRabbitMQ.ini", "errorQueue");
echo "RabbitMQ Log Listener started...\n";

$server->process_requests(function($request) {
    $msg = $request['message'] ?? "No message received";
    logAllErrors($msg); // Log to all logs + error.log
    echo "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n";
    return ["status" => "logged"];
});
