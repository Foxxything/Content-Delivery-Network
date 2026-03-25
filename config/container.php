<?php

use Foxxything\CDN\Core\DiscordAuth;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Wohali\OAuth2\Client\Provider\Discord;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    PhpRenderer::class => function (ContainerInterface $container) {
        return new PhpRenderer(dirname(__DIR__) . '/templates');
    },

    LoggerInterface::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['log'];

        $logger = new Logger($settings['name']);
        $logger->pushHandler(new StreamHandler($settings['path'], $settings['level']));
        return $logger;
    },

    DiscordAuth::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['discord'];

        return new DiscordAuth(
            clientId: $settings['clientId'],
            clientSecret: $settings['clientSecret'],
            redirectUri: $settings['redirectUri'],
            scopes: ['identify'],
        );
    },

];
