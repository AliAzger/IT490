<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}


$user_id = $_SESSION['user_id'] ?? 1; 

$group_id = (int)($_GET['group_id'] ?? 0);

$member_check = $conn->query("SELECT * FROM group_members WHERE group_id=$group_id AND user_id=$user_id");
if ($member_check->num_rows === 0) {
    die("You are not a member of this group.");
}
$member_info = $member_check->fetch_assoc();
$is_admin = $member_info['role'] === 'admin';

// Handle adding events
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['add_events'])) {
    foreach ($_POST['add_events'] as $event_id) {
        $event_id = $conn->real_escape_string($event_id);
        $check = $conn->query("SELECT * FROM group_events WHERE group_id=$group_id AND event_id='$event_id'");
        if ($check->num_rows === 0) {
            $conn->query("INSERT INTO group_events (group_id,event_id,added_by) VALUES ($group_id,'$event_id',$user_id)");
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['remove_events']) && $is_admin) {
    foreach ($_POST['remove_events'] as $event_id) {
        $event_id = $conn->real_escape_string($event_id);
        $conn->query("DELETE FROM group_events WHERE group_id=$group_id AND event_id='$event_id'");
    }
}

$group_events = $conn->query("
    SELECT ge.event_id, ae.name, ae.date_time, ae.venue, ae.url
    FROM group_events ge
    JOIN all_events ae ON ge.event_id = ae.event_id
    WHERE ge.group_id=$group_id
    ORDER BY ae.date_time ASC
");

$watchlist = $conn->query("SELECT * FROM saved_events ORDER BY date_time ASC");
?>

<!DOCTYPE html>
<html>
	<head>
	<meta charset="UTF-8">
	<title>Group Events</title>
<style>
	body { font-family: Arial; margin: 20px; }
	.event { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
	.event h2 { margin-top: 0; }
	.event-actions { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
	.view-btn { text-decoration: none; color: #007BFF; }
	.view-btn:hover { color: #0056b3; }
	input[type=submit] { padding: 5px 10px; border-radius: 4px; border: none; background-color: #28a745; color: white; cursor: pointer; margin-top: 10px; }
	input[type=submit]:hover { background-color: #218838; }
	form { margin-bottom: 30px; }
</style>
	</head>
<body>

<?php include('header.php'); ?>

<h1></h1>

<h2>Events in this Group</h2>
<form method="post">
<div class="results">
<?php
if ($group_events->num_rows > 0) {
    while ($event = $group_events->fetch_assoc()) {
        echo "<div class='event'>";
        echo "<h2>" . htmlspecialchars($event['name']) . "</h2>";
        echo "<p><strong>Date:</strong> " . htmlspecialchars($event['date_time']) . "</p>";
        echo "<p><strong>Venue:</strong> " . htmlspecialchars($event['venue']) . "</p>";
        echo "<div class='event-actions'>";
        echo "<a class='view-btn' href='" . htmlspecialchars($event['url']) . "' target='_blank'>View Event</a>";

        if ($is_admin) {
            echo "<label style='margin-left:10px;'>";
            echo "<input type='checkbox' name='remove_events[]' value='" . htmlspecialchars($event['event_id']) . "'> Remove";
            echo "</label>";
        }

        echo "</div></div>";
    }
} else {
    echo "<p>No events in this group yet.</p>";
}
?>
<input type="submit" value="Remove Selected Events" <?php echo $is_admin ? "" : "disabled"; ?>>
</div>
</form>

<h2>Add Events to Group</h2>
<form method="post">
<div class="results">
<?php
if ($watchlist->num_rows > 0) {
    while ($event = $watchlist->fetch_assoc()) {
        echo "<div class='event'>";
        echo "<h2>" . htmlspecialchars($event['event_name']) . "</h2>";
        echo "<p><strong>Date:</strong> " . htmlspecialchars($event['date_time']) . "</p>";
        echo "<p><strong>Venue:</strong> " . htmlspecialchars($event['venue']) . "</p>";
        echo "<label>";
        echo "<input type='checkbox' name='add_events[]' value='" . htmlspecialchars($event['event_id']) . "'> Add to Group";
        echo "</label>";
        echo "</div>";
    }
} else {
    echo "<p>No events in your watchlist to add.</p>";
}
?>
<input type="submit" value="Add Selected Events">
</div>
</form>

</body>
</html>

