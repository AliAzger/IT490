<?php
session_start();
include 'database.php';

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$rabbitHost = '172.25.28.168';
$rabbitPort = 5672;
$rabbitUser = 'test';
$rabbitPassword = 'test';
$vhost = 'testHost';
$queueName = 'testQueue';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['username'] = $username;

        try {
            $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $vhost);
            $channel = $connection->channel();
            $channel->queue_declare($queueName, false, true, false, false);

            $msgBody = json_encode([
                'event' => 'login',
                'username' => $username,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $msg = new AMQPMessage($msgBody, ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', $queueName);

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            error_log("RabbitMQ login error: " . $e->getMessage());
        }

        header("Location: hometest.php");
        exit();
    } else {
        echo "<script>alert('Invalid username or password, please try again');window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

