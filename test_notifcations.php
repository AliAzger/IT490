<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
include 'database.php'; 

date_default_timezone_set('America/New_York');

$endingSoonSeconds = 3600;

// Fetch all users
$users = $conn->query("SELECT * FROM account");

while ($user = $users->fetch_assoc()) {
    $user_id = $user['id'];
    $email = $user['email'];
    $full_name = $user['full_name'];

    $events_res = $conn->query("SELECT * FROM saved_events WHERE user_id = $user_id");

    $endingSoonEvents = [];
    while ($event = $events_res->fetch_assoc()) {
        $event_time = strtotime($event['date_time']);
        $now = time();
        $diff = $event_time - $now;

        if ($diff > 0 && $diff <= $endingSoonSeconds) {
            $endingSoonEvents[] = $event;
        }
    }

    if (!empty($endingSoonEvents)) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'alishahsamand50@gmail.com'; 
            $mail->Password = 'xyvi rjjf hoqh lxwb';   
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('your_email@example.com', 'Event Tracker');
            $mail->addAddress($email, $full_name);

            $mail->isHTML(true);
            $mail->Subject = 'Events Ending Soon in Your Watchlist';

            $body = "<h3>Hi " . ($full_name) . ",</h3>";
            $body .= "<p>The following events in your watchlist are ending soon:</p><ul>";
            foreach ($endingSoonEvents as $e) {
                $body .= "<li><a href='" . ($e['url']) . "'>" 
                         . ($e['event_name']) . "</a> — " 
                         . ($e['date_time']) . " at " 
                         . ($e['venue']) . ", " 
                         . ($e['city']) . "</li>";
            }
            $body .= "</ul><p>Check them out before it's too late!</p>";

            $mail->Body = $body;
            $mail->send();
            echo " Notification sent to $email<br>";
        } catch (Exception $ex) {
            echo " Failed to send to $email: {$mail->ErrorInfo}<br>";
        }
    } else {
        echo "No ending soon events for $email<br>";
    }
}
?>

