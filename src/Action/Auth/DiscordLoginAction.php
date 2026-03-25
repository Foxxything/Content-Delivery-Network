<?php

namespace Foxxything\CDN\Action\Auth;

use Foxxything\CDN\Core\DiscordAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class DiscordLoginAction
{
    public function __construct(
        private DiscordAuth $auth,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $url = $this->auth->getAuthorizationUrl();

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}