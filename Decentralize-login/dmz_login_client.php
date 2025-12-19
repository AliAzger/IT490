<?php

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/Logger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPTimeoutException;

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
$ch->exchange_declare(
  $config['rabbit']['exchange'],
  $config['rabbit']['exchange_type'],
  false,
  true,
  false
);

$logger = new LoggerX($logFile, $ch, $config['rabbit']['exchange']);

list($callbackQueue,,) = $ch->queue_declare("", false, false, true, false);

$correlationId = bin2hex(random_bytes(16));
$response = null;

$ch->basic_consume(
  $callbackQueue,
  '',
  false,
  true,
  false,
  false,
  function ($msg) use (&$response, $correlationId) {
    if ($msg->get('correlation_id') === $correlationId) {
      $response = $msg->body;
    }
  }
);

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

if (!$username || !$password) {
  echo "Usage: php dmz_login_client.php <user> <pass>\n";
  exit(1);
}

$payload = [
  'username' => $username,
  'password' => $password
];

$logger->log("info", $svc, "Sending login request", ['user' => $username]);

$msg = new AMQPMessage(json_encode($payload), [
  'content_type' => 'application/json',
  'correlation_id' => $correlationId,
  'reply_to' => $callbackQueue
]);

$ch->basic_publish($msg, $config['rabbit']['exchange'], "auth.request");

$start = time();
while ($response === null) {
  try {
    $ch->wait(null, false, 1);
  } catch (AMQPTimeoutException $e) {
    if (time() - $start > 8) {
      echo "ERROR: timeout\n";
      exit(2);
    }
  }
}

echo $response . PHP_EOL;

$ch->close();
$conn->close();

