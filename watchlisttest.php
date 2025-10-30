<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove'], $_POST['event_id'])) {
        $event_id = $conn->real_escape_string($_POST['event_id']);
        $conn->query("DELETE FROM saved_events WHERE event_id = '$event_id'");
        header("Location: watchlisttest.php");
        exit;
    }

    if (!empty($_POST['attended_events'])) {
        foreach ($_POST['attended_events'] as $attended_id) {
            // Check if already in attended_events table
            $check = $conn->query("SELECT * FROM attended_events WHERE event_id = '$attended_id'");
            if ($check->num_rows === 0) {
                // Get event details from saved_events
                $res = $conn->query("SELECT * FROM saved_events WHERE event_id = '$attended_id'");
                if ($row = $res->fetch_assoc()) {
                    $event_id = $conn->real_escape_string($row['event_id']);
                    $event_name = $conn->real_escape_string($row['event_name']);
                    $date_time = $conn->real_escape_string($row['date_time']);
                    $venue = $conn->real_escape_string($row['venue']);
                    $url = $conn->real_escape_string($row['url']);
                    $city = $event['_embedded']['venues'][0]['city']['name'] ?? '';
                    $conn->query("INSERT INTO attended_events (event_id, event_name, date_time, venue, city, url)
                                 VALUES ('$event_id', '$event_name', '$date_time', '$venue', '$city', '$url')");
                }
            }
        }
    }
}

$result = $conn->query("SELECT * FROM saved_events ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Watchlist</title>
    <style>
        body { 
			font-family: Arial;
			margin: 20px; }
        .event { 
			border: 1px solid #ccc; 
			padding: 15px; margin-bottom: 10px; 
			border-radius: 5px;
		}
        .event h2 { 
			margin-top: 0; 
		}
        .event-actions { 
			display: flex; 
			align-items: center; gap: 8px; 
		}
        .view-btn {
			text-decoration: none; 
			color: #007BFF;
		}
        .view-btn:hover { 
			color: #0056b3;
		}
        .remove-btn { 
			padding: 2px 6px; 
			background: #ff4d4d; 
			color: white; 
			border: none; 
			border-radius: 4px;
			cursor: pointer;
		}
        .remove-btn:hover { 
			background: #cc0000;
		}
    </style>
</head>
<body>

<?php include('header.php'); ?>
<h1></h1>

<form method="post" action="">
<div class="results">
<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='event'>";
        echo "<h2>" . ($row['event_name']) . "</h2>";
        echo "<p><strong>Date:</strong> " . ($row['date_time']) . "</p>";
	echo "<p><strong>Venue:</strong> " . ($row['venue']) . "</p>";
	

        echo "<div class='event-actions'>";
        echo "<a class='view-btn' href='" . ($row['url']) . "' target='_blank'>View Event</a>";

        // Remove button
        echo "<button type='submit' name='remove' value='1' class='remove-btn'>Remove</button>";
        echo "<input type='hidden' name='event_id' value='" . ($row['event_id']) . "'>";

        // Checkbox to mark as attended
        echo "<label style='margin-left: 10px;'>";
        echo "<input type='checkbox' name='attended_events[]' value='" . ($row['event_id']) . "'> Mark as Attended";
        echo "</label>";

        echo "</div>";
        echo "</div>";
    }
} else {
    echo "<p>Your watchlist is empty.</p>";
}
?>
</div>

<input type="submit" value="Update Attended">
</form>

</body>
</html>


