<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 1; 

if (isset($_POST['create_group'])) {
    $name = $conn->real_escape_string($_POST['group_name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $conn->query("INSERT INTO groupstab (group_name, description, created_by) VALUES ('$name','$desc',$user_id)");
    $group_id = $conn->insert_id;
    $conn->query("INSERT INTO group_members (group_id,user_id,role) VALUES ($group_id,$user_id,'admin')");
}

if (isset($_POST['join_group'])) {
    $join_id = (int)$_POST['join_group_id'];
    // Check if already a member
    $check = $conn->query("SELECT * FROM group_members WHERE group_id=$join_id AND user_id=$user_id");
    if ($check->num_rows === 0) {
        $conn->query("INSERT INTO group_members (group_id,user_id) VALUES ($join_id,$user_id)");
    }
}

$my_groups = $conn->query("
    SELECT g.*, gm.role 
    FROM groupstab g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id=$user_id
    ORDER BY g.created_at DESC
");

$public_groups = $conn->query("
    SELECT * FROM groupstab
    WHERE id NOT IN (SELECT group_id FROM group_members WHERE user_id=$user_id)
");
?>

<!DOCTYPE html>
<html>
	<head>	
	<meta charset="UTF-8">
	<title>Groups</title>
<style>
	body { font-family: Arial; margin: 20px; }
	.group { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
	.group h2 { margin-top: 0; }
	.group-actions { display: flex; gap: 10px; margin-top: 8px; }
	.create-group-form { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
	input[type=text], textarea { width: 100%; margin-top: 5px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; }
	input[type=submit] { padding: 5px 10px; margin-top: 10px; border-radius: 4px; border: none; background-color: #28a745; color: white; cursor: pointer; }
	input[type=submit]:hover { background-color: #218838; }
	.view-btn { text-decoration: none; color: #007BFF; }
	.view-btn:hover { color: #0056b3; }
</style>
	</head>
<body>

<?php include('header.php'); ?>

<h1></h1>

<div class="create-group-form">
<h2>Create a New Group</h2>
<form method="post">
<label>Group Name:<br><input type="text" name="group_name" required></label><br>
<label>Description:<br><textarea name="description"></textarea></label><br>
<input type="submit" name="create_group" value="Create Group">
</form>
</div>

<h2>My Groups</h2>
<div class="results">
<?php
if ($my_groups->num_rows > 0) {
    while ($g = $my_groups->fetch_assoc()) {
        echo "<div class='group'>";
        echo "<h2>" . htmlspecialchars($g['group_name']) . " (" . $g['role'] . ")</h2>";
        if ($g['description']) echo "<p>" . htmlspecialchars($g['description']) . "</p>";
	echo "<div class='group-actions'>";
	echo "<a class='view-btn' href='group_members.php?group_id=" . $g['id'] . "'>Manage Members</a>";

        echo "<a class='view-btn' href='group_events.php?group_id=" . $g['id'] . "'>View / Add Events</a>";
        echo "</div></div>";
    }
} else { echo "<p>You are not in any groups yet.</p>"; }
?>
</div>

<h2>Other Groups</h2>
<div class="results">
<?php
if ($public_groups->num_rows > 0) {
    while ($g = $public_groups->fetch_assoc()) {
        echo "<div class='group'>";
        echo "<h2>" . htmlspecialchars($g['group_name']) . "</h2>";
        if ($g['description']) echo "<p>" . htmlspecialchars($g['description']) . "</p>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='join_group_id' value='" . $g['id'] . "'>";
        echo "<input type='submit' name='join_group' value='Join Group'>";
        echo "</form></div>";
    }
} else { echo "<p>No other groups available.</p>"; }
?>
</div>

</body>
</html>

