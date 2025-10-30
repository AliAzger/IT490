<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}


include 'database.php';

$recommendations = $conn->query("
    SELECT * FROM all_events
    WHERE venue IN (
        SELECT DISTINCT venue FROM attended_events
    )
    AND event_id NOT IN (SELECT event_id FROM saved_events)
    AND event_id NOT IN (SELECT event_id FROM attended_events)
    ORDER BY date_time ASC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recommended Events</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .event { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .event h2 { margin-top: 0; }
        .event-actions { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .view-btn { text-decoration: none; color: #007BFF; }
        .view-btn:hover { color: #0056b3; }
        .save-btn { padding: 2px 6px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .save-btn:hover { background: #218838; }
    </style>
</head>
<body>

<?php include('header.php'); ?>

<h1></h1>

<form method="post" action="recommendations.php">
<div class="results">
<?php
if ($recommendations->num_rows > 0) {
    while ($event = $recommendations->fetch_assoc()) {
        echo "<div class='event'>";
        echo "<h2>" . ($event['name']) . "</h2>";
        echo "<p><strong>Date:</strong> " . ($event['date_time']) . "</p>";
        echo "<p><strong>Venue:</strong> " . ($event['venue']) . "</p>";

        echo "<div class='event-actions'>";
        echo "<a class='view-btn' href='" . ($event['url']) . "' target='_blank'>View Event</a>";


        
        // Add to watchlist checkbox
        
        echo "<label style='margin-left:10px;'>";
        echo "<input type='checkbox' name='save_events[]' value='" . ($event['event_id']) . "'> Add to Watchlist";
        echo "</label>";




        
        echo "</div>";
        echo "</div>";
    }
} else {
    echo "<p>No recommendations available yet. Attend more events to get personalized suggestions!</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_events'])) {
    foreach ($_POST['save_events'] as $eventId) {
        // Fetch event details from all_events
        $res = $conn->query("SELECT * FROM all_events WHERE event_id='" . $conn->real_escape_string($eventId) . "'");
        if ($row = $res->fetch_assoc()) {
            $event_id = $conn->real_escape_string($row['event_id']);
            $event_name = $conn->real_escape_string($row['name']);
            $date_time = $conn->real_escape_string($row['date_time']);
            $venue = $conn->real_escape_string($row['venue']);
            $url = $conn->real_escape_string($row['url']);

            $check = $conn->query("SELECT * FROM saved_events WHERE event_id='$event_id'");
            if ($check->num_rows === 0) {
                $conn->query("INSERT INTO saved_events (event_id, event_name, date_time, venue, url)
                             VALUES ('$event_id', '$event_name', '$date_time', '$venue', '$url')");
            }
        }
    }
}
?>
</div>

<input type="submit" value="Save Selected Events">
</form>

</body>
</html>

