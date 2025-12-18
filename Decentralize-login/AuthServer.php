<?php
// AuthServer.php
require_once('rabbitMQLib.php');
require_once('logger.php');
require_once('UserStore.php');
require_once('vendor/autoload.php'); // Required for JWT library

use Firebase\JWT\JWT;

$secretKey = "super_secret_key";
$server = new rabbitMQServer("host.ini", "testServer");

echo "Auth Server started...\n";

$server->process_requests(function($request) use ($secretKey) {
    try {
        $type = $request['type'] ?? '';
        $username = $request['username'] ?? '';
        $password = $request['password'] ?? '';

        if ($type === "register") {
            createUser($username, $password);
            logAll("User registered: $username");
            return ["status" => "ok", "message" => "User registered successfully"];
        } elseif ($type === "login") {
            if (verifyUser($username, $password)) {
                $payload = ["username" => $username, "exp" => time() + 3600];
                $jwt = JWT::encode($payload, $secretKey, 'HS256');
                logAll("User logged in: $username");
                return ["status" => "ok", "token" => $jwt];
            }
            throw new Exception("Invalid credentials");
        }
    } catch (Exception $e) {
        logAllErrors($e->getMessage());
        // Use unified host.ini for error logging
        $logClient = new rabbitMQClient("host.ini", "errorQueue");
        $logClient->publish(["message" => $e->getMessage()]);
        return ["status" => "error", "message" => $e->getMessage()];
    }
});