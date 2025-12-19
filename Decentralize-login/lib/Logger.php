<?php
use PhpAmqpLib\Message\AMQPMessage;

class LoggerX {
  private string $filePath;
  private $channel;
  private string $exchange;

  public function __construct(string $filePath, $channel = null, string $exchange = '') {
    $this->filePath = $filePath;
    $this->channel = $channel;
    $this->exchange = $exchange;
    if (!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0777, true);
  }

  public function log(string $level, string $service, string $msg, array $context = []) {
    $entry = [
      'ts' => date('c'),
      'level' => strtoupper($level),
      'service' => $service,
      'msg' => $msg,
      'context' => $context,
    ];
    file_put_contents($this->filePath, json_encode($entry) . PHP_EOL, FILE_APPEND);

    // also send to rabbit logs exchange if channel available
    if ($this->channel && $this->exchange) {
      $routingKey = "logs.$service." . strtolower($level);
      $amqpMsg = new AMQPMessage(json_encode($entry), ['content_type' => 'application/json']);
      $this->channel->basic_publish($amqpMsg, $this->exchange, $routingKey);
    }
  }
}
