<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

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

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);

    $action = ''; // track insert or update

    $check = $conn->query("SELECT * FROM account WHERE email='$email'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE account SET full_name='$name' WHERE email='$email'");
        $message = "Account updated ";
	$action = 'update';

    } else {
	        
	$conn->query("INSERT INTO account (full_name, email) VALUES ('$name', '$email')");
        $message = "Account created ";
        $action = 'create';
    }

    try {
        $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $vhost);
        $channel = $connection->channel();
        $channel->queue_declare($queueName, false, true, false, false);

        $msgBody = json_encode([
            'event' => 'account_save',
            'username' => $_SESSION['username'] ?? 'guest',
            'email' => $email,
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $msg = new AMQPMessage($msgBody, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, '', $queueName);

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        error_log("RabbitMQ account save error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account</title>
    <style>
        body { 
			font-family: Arial; 
			margin: 20px; 
		}
        form { max-width: 400px; 
			  margin: auto; 
			 }
        label { display: block; 
			   margin-top: 10px; 
			  }
        input[type="text"], input[type="email"] { 
			width: 100%; 
			padding: 8px; 
			margin-top: 5px; 
		}
        input[type="submit"] { 
			margin-top: 15px; 
			padding: 8px 12px; 
			cursor: pointer; 
		}
        .message { 
			margin-top: 15px; 
			color: green; 
			text-align: center; 
		}
    </style>
</head>
<body>

<?php include('header.php'); ?>

<h1> </h1>

<form method="post" action="">
    <label for="full_name">Full Name:</label>
    <input type="text" name="full_name" id="full_name" required>

    <label for="email">Email:</label>
    <input type="email" name="email" id="email" required>

    <input type="submit" value="Save">
</form>

<?php if ($message) echo "<p class='message'>$message</p>"; ?>

</body>
</html>

