<?php
return [
  'rabbit' => [
    'host' => 'RABBITMQ_IP',
    'port' => 5672,
    'user' => 'it490',
    'pass' => 'StrongPasswordHere',
    'vhost' => 'it490_vhost',
    'exchange' => 'it490_exchange',
    'exchange_type' => 'topic', // change to direct/fanout/topic/headers
  ],
  'logs' => [
    'local_log_dir' => __DIR__ . '/logs',
  ]
];
