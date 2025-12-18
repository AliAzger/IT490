<?php
// AuthClient.php
require_once('rabbitMQLib.php'); // Updated extension
require_once('logger.php');

if ($argc < 4) {
    echo "Usage: php AuthClient.php <register|login> <username> <password>\n";
    exit(1);
}

$type = $argv[1];
$username = $argv[2];
$password = $argv[3];

try {
    // Loads 'testServer' section from 'host.ini'
    $client = new rabbitMQClient("host.ini", "testServer"); 
    $request = [
        "type" => $type,
        "username" => $username,
        "password" => $password
    ];
    $response = $client->send_request($request);
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    logAllErrors("Client error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}