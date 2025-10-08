<?php
$servername = "172.25.28.168";
$username =  "testUser"
$password = "Jhmwty9810!"
$dbname = "students"

$conn = new mysql($servername, $username, $password, $dbname);

if ($conn->connect_error){
        die("connection failed: " . $conn->connect_error);
}
$data = json_decode(file_get_contents("php://input"), true);
$user = $data["username"];
$user = $data["password"];

$sql = "INSERT INTO users (username, password) VALUES ('$user', $pass')";
if ($cnn ->query($sql) === TRUE){
        echo json_encode(["status" => "success"]);
} else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
}
$conn0->close();
?>
