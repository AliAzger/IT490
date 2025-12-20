<?php

use PhpAmqpLib\Message\AMQPMessage;

class LoggerX
{
    private string $file;
    private $channel;
    private string $exchange;

    public function __construct(string $file, $channel, string $exchange)
    {
        $this->file = $file;
        $this->channel = $channel;
        $this->exchange = $exchange;
    }

    public function log(string $level, string $service, string $message, array $context = []): void
    {
        $entry = json_encode([
            'ts' => time(),
            'level' => $level,
            'service' => $service,
            'message' => $message,
            'context' => $context
        ]);

        // Write local log
        file_put_contents($this->file, $entry . PHP_EOL, FILE_APPEND);

        // Publish to RabbitMQ
        $msg = new AMQPMessage($entry, ['content_type' => 'application/json']);
        $routingKey = "logs.$level.$service";

        $this->channel->basic_publish($msg, $this->exchange, $routingKey);
    }
}

