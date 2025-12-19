<?php
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
$filePath = $logDir . "/log_listener.log";

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

list($queue, ,) = $ch->queue_declare("", false, false, true, false);
$ch->queue_bind($queue, $exchange, "logs.#");

file_put_contents($filePath, "Log listener started: " . date('c') . PHP_EOL, FILE_APPEND);

$ch->basic_consume($queue, '', false, true, false, false,
  function($msg) use ($filePath) {
    file_put_contents($filePath, $msg->body . PHP_EOL, FILE_APPEND);
  }
);

while ($ch->is_consuming()) {
  $ch->wait();
}
