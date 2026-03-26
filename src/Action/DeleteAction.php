<?php

namespace Foxxything\CDN\Action;

use Foxxything\CDN\Core\PathGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class DeleteAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $userDir  = PathGuard::resolveUserDir(__DIR__ . '/../../public/uploads');

        if (!$userDir) {
            return $response->withStatus(empty($_SESSION['user']) ? 401 : 400);
        }

        $filename = basename($request->getAttribute('filename'));
        $path     = $userDir . '/' . $filename;

        if (PathGuard::isInside($path, $userDir) && file_exists($path)) {
            unlink($path);
        }

        return $response->withHeader('Location', '/upload')->withStatus(302);
    }
}