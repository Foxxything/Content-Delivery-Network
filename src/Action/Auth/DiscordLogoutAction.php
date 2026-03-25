<?php

namespace Foxxything\CDN\Action\Auth;

use Foxxything\CDN\Core\DiscordAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class DiscordLogoutAction
{
    public function __construct(
        private DiscordAuth $auth,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $this->auth->clearSession();

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }
}