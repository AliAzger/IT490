<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

include 'ticketmaster_api.php';
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

$keyword = $_GET['keyword'] ?? '';
$city = $_GET['city'] ?? '';

$events = getTicketmasterEvents($keyword, $city);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_events'])) {
    $user_id = $_SESSION['user_id'] ?? 1;

    $savedCount = 0; 

    foreach ($_POST['selected_events'] as $selected_id) {

	    foreach ($events as $event) {
	        if ($event['id'] === $selected_id) {
		       
			$event_id = $conn->real_escape_string($event['id']);
			
			$event_name = $conn->real_escape_string($event['name']);
	
			$date_time = $conn->real_escape_string(($event['dates']['start']['localDate'] ?? '')
		
				. ' ' . ($event['dates']['start']['localTime'] ?? ''));
		       
			$venue = $conn->real_escape_string($event['_embedded']['venues'][0]['name'] ?? 'N/A');
		       
			$city = $conn->real_escape_string($event['_embedded']['venues'][0]['city']['name'] ?? '');
		       
			$url = $conn->real_escape_string($event['url']);

			
			$check = $conn->query("SELECT * FROM saved_events WHERE event_id = '$event_id' AND user_id = $user_id");
		   
			if ($check->num_rows === 0) {
		   
		
		
			$sql = "INSERT INTO saved_events (event_id, event_name, date_time, venue, url, city, user_id)
                            VALUES ('$event_id', '$event_name', '$date_time', '$venue', '$url', '$city', $user_id)";
                    if ($conn->query($sql)) {
                        $savedCount++;
                    }
                }
            }
        }
    }




    if ($savedCount > 0) {
        try {
            $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $vhost);
            $channel = $connection->channel();
            $channel->queue_declare($queueName, false, true, false, false);

            $msgBody = json_encode([
                'event' => 'save_selected_events',
                'username' => $_SESSION['username'] ?? 'guest',
                'saved_count' => $savedCount,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $msg = new AMQPMessage($msgBody, ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', $queueName);

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            error_log("RabbitMQ save error: " . $e->getMessage());
        }
    }

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .event { border: 1px solid #ccc; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .event h2 { margin-top: 0; }
        .event-actions { display: flex; align-items: center; gap: 8px; }
        .searchform { margin-bottom: 20px; }
    </style>
</head>
<body>
<?php include('header.php'); ?>

<div class="searchform">
    <form method="get">
        <input type="text" name="keyword" placeholder="Keyword" value="<?= htmlspecialchars($keyword) ?>">
        <input type="text" name="city" placeholder="City" value="<?= htmlspecialchars($city) ?>">
        <button type="submit">Search</button>
    </form>
</div>

<form method="post" action="">
    <div class="results">
        <?php
        if (!empty($events)) {
            foreach ($events as $event) {
                $venue = $event['_embedded']['venues'][0]['name'] ?? 'N/A';
                $address = $event['_embedded']['venues'][0]['address']['line1'] ?? '';

                echo "<div class='event'>";
                echo "<h2>" . htmlspecialchars($event['name']) . "</h2>";
                echo "<p><strong>Date:</strong> " . htmlspecialchars(($event['dates']['start']['localDate'] ?? '') . ' ' . ($event['dates']['start']['localTime'] ?? '')) . "</p>";
                echo "<p><strong>Venue:</strong> " . htmlspecialchars($venue . ' ' . $address) . "</p>";

                echo "<div class='event-actions'>";
                echo "<a href='" . htmlspecialchars($event['url']) . "' target='_blank'>View Event</a>";
                echo "<input type='checkbox' name='selected_events[]' value='" . htmlspecialchars($event['id']) . "'>";
                echo "</div>";

                echo "</div>";
            }
        } else {
            echo "<p>No events found.</p>";
        }
        ?>
    </div>
    <input type="submit" value="Save Selected Events">
</form>

</body>
</html>
