<?php
//makes sure the user is not logged into the website when in incognito
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();

  
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Home</title>
</head>
<h1>Test</h1>
<body>
</body>
</html>
