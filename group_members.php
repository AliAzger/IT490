<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
include 'database.php';

$user_id = $_SESSION['user_id'] ?? 1; 
$group_id = (int)($_GET['group_id'] ?? 0);


$check = $conn->query("SELECT * FROM group_members WHERE group_id=$group_id AND user_id=$user_id");
if ($check->num_rows === 0) {
    die("You are not a member of this group.");
}
$member_info = $check->fetch_assoc();
$is_admin = $member_info['role'] === 'admin';

if ($is_admin && isset($_POST['add_user'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $result = $conn->query("SELECT id FROM users WHERE username='$username'");

    if ($result->num_rows > 0) {
        $user_to_add = $result->fetch_assoc()['id'];
        // Check if already in group
        $exists = $conn->query("SELECT * FROM group_members WHERE group_id=$group_id AND user_id=$user_to_add");
        if ($exists->num_rows === 0) {
            $conn->query("INSERT INTO group_members (group_id, user_id, role) VALUES ($group_id, $user_to_add, 'member')");
            echo "<p Added $username to the group!</p>";
        } else {
            echo "<p  $username is already in the group.</p>";
        }
    } else {
        echo "<p User not found.</p>";
    }
}

if ($is_admin && isset($_POST['remove_user_id'])) {
    $remove_id = (int)$_POST['remove_user_id'];
    if ($remove_id !== $user_id) { 
        $conn->query("DELETE FROM group_members WHERE group_id=$group_id AND user_id=$remove_id");
        echo "<p User removed.</p>";
    }
}

$members = $conn->query("
    SELECT gm.*, u.username 
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id=$group_id
    ORDER BY gm.role DESC, u.username ASC
");
?>

<!DOCTYPE html>
<html>
	<head>
	<meta charset="UTF-8">
	<title>Manage Group Members</title>
<style>
	body { 
		font-family: Arial;
		margin: 20px; 
	}
	.member { 
		border: 1px solid #ccc; 
			 padding: 10px; 
		margin-bottom: 10px; 
		border-radius: 5px; 
	}
	input[type=text] { 
		padding: 5px; 
		width: 200px; 
	}
	input[type=submit] { 
		padding: 5px 10px; 
		border: none; 
		border-radius: 4px; 
		background-color: #007BFF; 
		color: white; 
		cursor: pointer;
	}
	input[type=submit]:hover { 
		background-color: #0056b3;
	}
	.remove-btn { 
		background-color: #dc3545; }
	.remove-btn:hover { 
		background-color: #b02a37; 
	}
	</style>
	</head>
<body>

<?php include('header.php'); ?>

<h1></h1>

<?php if ($is_admin): ?>
<form method="post" style="margin-bottom:20px;">
    <label>Add User by Username: 
        <input type="text" name="username" required>
    </label>
    <input type="submit" name="add_user" value="Add User">
</form>
<?php endif; ?>

<h2>Current Members</h2>
<div class="results">
<?php
if ($members->num_rows > 0) {
    while ($m = $members->fetch_assoc()) {
        echo "<div class='member'>";
        echo "<strong>" . ($m['username']) . "</strong> (" . $m['role'] . ")";
        if ($is_admin && $m['user_id'] != $user_id) {
            echo "<form method='post' style='display:inline; margin-left:10px;'>
                    <input type='hidden' name='remove_user_id' value='" . $m['user_id'] . "'>
                    <input type='submit' class='remove-btn' value='Remove'>
                  </form>";
        }
        echo "</div>";
    }
} else {
    echo "<p>No members yet.</p>";
}
?>
</div>

</body>
</html>

