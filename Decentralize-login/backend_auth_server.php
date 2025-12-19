<?php
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require __DIR__ . '/lib/Logger.php';

$svc = "backend";
$logFile = __DIR__ . "/logs/backend.log";

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

$ch->queue_declare("auth.request", false, true, false, false);
$ch->queue_bind("auth.request", $exchange, "auth.request");

$logger->log("info", $svc, "Backend auth server started");

function checkCredentials(string $u, string $p): bool {
  // TODO: Replace with DB lookup (password_verify against hashed passwords)
  // Example stub:
  return ($u === "test" && $p === "pass123");
}

$callback = function($req) use ($ch, $logger, $svc) {
  $body = json_decode($req->body, true);
  $u = $body['username'] ?? '';
  $p = $body['password'] ?? '';

  $logger->log("info", $svc, "Login request received", ['username' => $u]);

  $ok = false;
  try {
    $ok = checkCredentials($u, $p);
  } catch (Throwable $e) {
    $logger->log("error", $svc, "Auth check crashed", ['err' => $e->getMessage()]);
  }

  $resp = [
    'ok' => $ok,
    'username' => $u,
    'token' => $ok ? bin2hex(random_bytes(24)) : null,
    'ts' => time()
  ];

  $msg = new AMQPMessage(json_encode($resp), [
    'content_type' => 'application/json',
    'correlation_id' => $req->get('correlation_id')
  ]);

  $replyTo = $req->get('reply_to');
  $ch->basic_publish($msg, '', $replyTo);

  $logger->log($ok ? "info" : "warning", $svc, "Login processed", ['ok' => $ok]);
  $ch->basic_ack($req->getDeliveryTag());
};

$ch->basic_qos(null, 1, null);
$ch->basic_consume("auth.request", '', false, false, false, false, $callback);

while ($ch->is_consuming()) {
  $ch->wait();
}
