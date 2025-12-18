<?php
require_once('rabbitMQLib.inc');
require_once('logger.php');
require_once('UserStore.php');
require_once('vendor/autoload.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = "super_secret_key";

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
echo "Auth Server started...\n";

$server->process_requests(function($request) use ($secretKey) {
    try {
        $type = $request['type'] ?? '';
        $username = $request['username'] ?? '';
        $password = $request['password'] ?? '';
        $exchangeType = $request['exchangeType'] ?? 'direct';

        if ($type === "register") {
            createUser($username, $password);
            logAll("User registered: $username");
            $response = ["status" => "ok", "message" => "User registered successfully"];
        } elseif ($type === "login") {
            if (verifyUser($username, $password)) {
                $payload = [
                    "username" => $username,
                    "iat" => time(),
                    "exp" => time() + 3600
                ];
                $jwt = JWT::encode($payload, $secretKey, 'HS256');
                logAll("User logged in: $username");
                $response = ["status" => "ok", "token" => $jwt];
            } else {
                throw new Exception("Invalid credentials for user: $username");
            }
        } else {
            throw new Exception("Unknown request type: $type");
        }

        return $response;

    } catch (Exception $e) {
        logAllErrors($e->getMessage());

        // Optional: send error message to RabbitMQ
        try {
            $client = new rabbitMQClient("testRabbitMQ.ini", "errorQueue");
            $client->publish(["type" => "error", "message" => $e->getMessage()]);
        } catch (Exception $inner) {
            logAllErrors("Failed to publish error to RabbitMQ: " . $inner->getMessage());
        }

        return ["status" => "error", "message" => $e->getMessage()];
    }
});
