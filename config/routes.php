<?php

use Foxxything\CDN\Action\Auth\DiscordCallbackAction;
use Foxxything\CDN\Action\Auth\DiscordLoginAction;
use Foxxything\CDN\Action\Auth\DiscordLogoutAction;
use Foxxything\CDN\Action\DeleteAction;
use Foxxything\CDN\Action\HomeAction;
use Foxxything\CDN\Action\ImageAction;
use Foxxything\CDN\Action\UploadAction;
use Foxxything\CDN\Action\UploadPostAction;
use Slim\App;

return function (App $app) {
    $app->get('/', HomeAction::class);

    $app->get('/auth/discord', DiscordLoginAction::class);
    $app->get('/auth/discord/callback', DiscordCallbackAction::class);
    $app->get('/auth/discord/logout', DiscordLogoutAction::class);

    $app->get('/upload',  UploadAction::class);
    $app->post('/upload', UploadPostAction::class);

    $app->post('/upload/delete/{filename:.+}', DeleteAction::class);

    $app->get('/image/{filename:.+}', ImageAction::class);
};
