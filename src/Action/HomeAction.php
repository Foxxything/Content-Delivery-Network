<?php

namespace Foxxything\CDN\Action;

use Foxxything\CDN\Core\DiscordAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final readonly class HomeAction
{
    public function __construct(
        private DiscordAuth $auth,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        if ($this->auth->isLoggedIn()) {
            return $response->withHeader('Location', '/upload')->withStatus(302);
        } else {
            return $response->withHeader('Location', '/auth/discord')->withStatus(302);
        }
    }
}