<?php

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

$file = $logDir . "/log_listener.log";

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

list($queue,,) = $ch->queue_declare("", false, false, true, false);
$ch->queue_bind($queue, $config['rabbit']['exchange'], "logs.#");

file_put_contents($file, "Log listener started\n", FILE_APPEND);

$ch->basic_consume(
  $queue,
  '',
  false,
  true,
  false,
  false,
  fn($msg) => file_put_contents($file, $msg->body . PHP_EOL, FILE_APPEND)
);

while ($ch->is_consuming()) {
  $ch->wait();
}

