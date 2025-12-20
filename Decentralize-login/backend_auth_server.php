<?php

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/Logger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
$ch->exchange_declare(
  $config['rabbit']['exchange'],
  $config['rabbit']['exchange_type'],
  false,
  true,
  false
);
//
$logger = new LoggerX($logFile, $ch, $config['rabbit']['exchange']);

$ch->queue_declare("auth.request", false, true, false, false);
$ch->queue_bind("auth.request", $config['rabbit']['exchange'], "auth.request");

$logger->log("info", $svc, "Backend auth server started");

function checkCredentials(string $u, string $p): bool
{
  return ($u === "test" && $p === "pass123");
}

$callback = function ($req) use ($ch, $logger, $svc) {

  $body = json_decode($req->body, true);
  $u = $body['username'] ?? '';
  $p = $body['password'] ?? '';

  $logger->log("info", $svc, "Login request received", ['user' => $u]);

  $ok = checkCredentials($u, $p);

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

  $ch->basic_publish($msg, '', $req->get('reply_to'));
  $req->ack();
};

$ch->basic_qos(null, 1, null);
$ch->basic_consume("auth.request", '', false, false, false, false, $callback);

while ($ch->is_consuming()) {
  $ch->wait();
}

