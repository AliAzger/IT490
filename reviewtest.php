<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

include 'database.php';
require_once __DIR__ . '/vendor/autoload.php';
        use PhpAmqpLib\Connection\AMQPStreamConnection;
        use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $event_id = $conn->real_escape_string($_POST['event_id']);
    $rating = (int)$_POST['rating'];
    $review = $conn->real_escape_string($_POST['review']);

    $sql = "INSERT INTO review_events (event_id, rating, review) VALUES ('$event_id', '$rating', '$review')";
    if ($conn->query($sql)) {

        $rabbitHost = '172.25.28.168';
        $rabbitPort = 5672;
        $rabbitUser = 'test';
        $rabbitPassword = 'test';
        $vhost = 'testHost';
        $queueName = 'testQueue';

        try {
            $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword, $vhost);
            $channel = $connection->channel();
            $channel->queue_declare($queueName, false, true, false, false);

            $msgBody = json_encode([
                'event' => 'submit_review',
                'username' => $_SESSION['username'] ?? 'guest',
                'event_id' => $event_id,
                'rating' => $rating,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $msg = new AMQPMessage($msgBody, ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', $queueName);

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            error_log("RabbitMQ review error: " . $e->getMessage());
        }
    }
}


$attended = $conn->query("SELECT * FROM attended_events ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attended Events</title>
    <style>
        body { 
            font-family: 
                Arial; 
            margin: 20px; 
        }
        .event { 
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px; 
            border-radius: 5px;
        }
        .event h2 { 
            margin-top: 0; 
        }
        .event-actions { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            margin-top: 8px; }
        .view-btn {
            text-decoration: none; 
            color: #007BFF;
        }
        .view-btn:hover { 
            color: #0056b3;
        }
        .review-form { 
            margin-top: 10px; 
        }
        .review-form textarea { 
            width: 100%; 
            height: 60px; 
        }
        .review-form select, .review-form input[type=submit] {
            margin-top: 5px; }
    </style>
</head>
<body>

<?php include('header.php'); ?>
<h1></h1>

<div class="results">
<?php
if ($attended->num_rows > 0) {
    while ($row = $attended->fetch_assoc()) {
        $event_id = $row['event_id'];
        echo "<div class='event'>";
        echo "<h2>" . ($row['event_name']) . "</h2>";
        echo "<p><strong>Date:</strong> " . ($row['date_time']) . "</p>";
        echo "<p><strong>Venue:</strong> " . ($row['venue']) . "</p>";
        echo "<div class='event-actions'>";
        echo "<a class='view-btn' href='" . ($row['url']) . "' target='_blank'>View Event</a>";
        echo "</div>";

        // Review form
        echo "<form method='post' class='review-form'>";
        echo "<input type='hidden' name='event_id' value='" . ($event_id) . "'>";
        echo "<label>Rating: <select name='rating'>";
        for ($i = 1; $i <= 5; $i++) {
            echo "<option value='$i'>$i Star" . ($i > 1 ? "s" : "") . "</option>";
        }
        echo "</select></label><br>";
        echo "<label>Review:<br><textarea name='review' placeholder='Write your review...'></textarea></label><br>";
        echo "<input type='submit' name='submit_review' value='Submit Review'>";
        echo "</form>";

        $reviews = $conn->query("SELECT * FROM review_events WHERE event_id='$event_id' ORDER BY created_at DESC");
        if ($reviews->num_rows > 0) {
            echo "<h4>Reviews:</h4>";
            while ($r = $reviews->fetch_assoc()) {
                echo "<p>⭐ " . ($r['rating']) . " - " . nl2br(($r['review'])) . "</p>";
            }
        }

        echo "</div>";
    }
} else {
    echo "<p>You haven’t marked any events as attended yet.</p>";
}
?>
</div>

</body>
</html>

