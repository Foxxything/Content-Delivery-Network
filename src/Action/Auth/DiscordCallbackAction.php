<?php

namespace Foxxything\CDN\Action\Auth;

use Foxxything\CDN\Core\DiscordAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class DiscordCallbackAction
{
    public function __construct(
        private DiscordAuth $auth,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $user = $this->auth->handleCallback(
            code:  $params['code'],
            state: $params['state'],
        );

        // Store what you need in session
        $_SESSION['user'] = [
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'email'    => $user->getEmail(),
            'avatar'   => $user->getAvatarHash(),
        ];

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }
}