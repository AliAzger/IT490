<?php
$servername = "172.25.199.66";
$username =  "testUser";
$password = "12345";
$dbname = "testdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error){
        die("connection failed: " . $conn->connect_error);
}
?>
