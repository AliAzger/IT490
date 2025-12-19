<?php

return [
    'rabbit' => [
        'host' => '172.25.64.157',   // your RabbitMQ VM IP
        'port' => 5672,
        'user' => 'it490',            // must match user created
        'pass' => 'StrongPassword#', // must match password
        'vhost' => 'it490_vhost',     // must match vhost
        'exchange' => 'it490_exchange',
        'exchange_type' => 'topic'
    ],
    'logs' => [
        'local_log_dir' => __DIR__ . '/logs',
    ]
];





