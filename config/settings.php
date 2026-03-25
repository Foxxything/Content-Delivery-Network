<?php

use Monolog\Level;


if (!isset($_ENV['LOG_LEVEL'])) {
    $level = 'error';
} else {
    $level = $_ENV['LOG_LEVEL'];
}

// Settings
$settings = [
    'debug' => $_ENV['APP_DEBUG'] ?? true,
    'log' => [
        'name' => 'app',
        'path' => __DIR__ . '/../logs/app.log',
        'level' => Level::fromName($level),
    ],
    'discord' => [
        'clientId' => $_ENV['DISCORD_CLIENT_ID'],
        'clientSecret' => $_ENV['DISCORD_CLIENT_SECRET'],
        'redirectUri' => $_ENV['DISCORD_REDIRECT_URI'],
    ],
    'app' => [
        'base_url' => $_ENV['APP_BASE_URL'] ?? 'http://localhost:8080',
    ],
];

return $settings;
