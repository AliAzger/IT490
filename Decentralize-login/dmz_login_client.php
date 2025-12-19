<?php
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require __DIR__ . '/lib/Logger.php';

$svc = "dmz";
$logFile = __DIR__ . "/logs/dmz.log";

$conn = new AMQPStreamConnection(
  $config['rabbit']['host'],
  $config['rabbit']['port'],
  $config['rabbit']['user'],
  $config['rabbit']['pass'],
  $config['rabbit']['vhost']
);
$ch = $conn->channel();

$exchange = $config['rabbit']['exchange'];
$exchangeType = $config['rabbit']['exchange_type'];
$ch->exchange_declare($exchange, $exchangeType, false, true, false);

$logger = new LoggerX($logFile, $ch, $exchange);

list($callbackQueue, ,) = $ch->queue_declare("", false, false, true, false);

$correlationId = bin2hex(random_bytes(16));
$response = null;

$ch->basic_consume($callbackQueue, '', false, true, false, false,
  function($msg) use (&$response, $correlationId) {
    if ($msg->get('correlation_id') === $correlationId) {
      $response = $msg->body;
    }
  }
);

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

if ($username === '' || $password === '') {
  echo "Usage: php dmz_login_client.php <username> <password>\n";
  exit(1);
}

$payload = [
  'action' => 'login',
  'username' => $username,
  'password' => $password,
  'client_ts' => time()
];

$logger->log("info", $svc, "Sending login request", ['username' => $username]);

$msg = new AMQPMessage(json_encode($payload), [
  'content_type' => 'application/json',
  'correlation_id' => $correlationId,
  'reply_to' => $callbackQueue
]);

$ch->queue_declare("auth.request", false, true, false, false);
$ch->basic_publish($msg, $exchange, "auth.request");

$start = time();
$timeout = 8; // seconds
while ($response === null) {
  $ch->wait(null, false, 1);
  if (time() - $start > $timeout) {
    $logger->log("error", $svc, "Login timed out waiting for backend reply");
    echo "ERROR: timeout\n";
    exit(2);
  }
}

$logger->log("info", $svc, "Received backend response", ['raw' => $response]);
echo $response . "\n";

$ch->close();
$conn->close();
