<?php
return [
    'host' => $_ENV['WEBSOCKET_HOST'] ?? 'localhost',
    'port' => $_ENV['WEBSOCKET_PORT'] ?? 8080,
    'allowed_origins' => [
        'http://localhost:3000',
        'https://bostarter.com'
    ],
    'ping_interval' => 30, // secondi
    'ping_timeout' => 10, // secondi
    'max_connections' => 1000,
    'max_message_size' => 4096, // bytes
]; 