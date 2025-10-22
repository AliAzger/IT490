<?php
include 'database.php'; 
require_once __DIR__ . '/vendor/autoload.php'; 

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ connection details
$rabbitHost = '172.25.28.168';    
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$vhost      = 'testHost';          
$queueName  = 'testQueue';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo "Username and password are required.";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {

        try {
            $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $vhost);
            $channel = $connection->channel();

            // make a queue
            $channel->queue_declare($queueName, false, true, false, false);

            // Send message for registering
            $msgBody = json_encode([
                'username' => $username,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $msg = new AMQPMessage($msgBody);
            $channel->basic_publish($msg, '', $queueName);
		
            
            $channel->close();
            $connection->close();

        } catch (Exception $e) {
            error_log("RabbitMQ error: " . $e->getMessage());
        }

        header("Location: index.html");
        exit();
    } else {
        echo "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

