<?php

namespace Foxxything\CDN\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class DeleteAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        if (empty($_SESSION['user'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $userId = $_SESSION['user']['id'];
        $filename = basename($request->getAttribute('filename')); // strip traversal

        $userDir = realpath(__DIR__ . '/../../public/uploads/' . $userId);
        $path = $userDir . '/' . $filename;

        // Confirm the resolved path actually lives inside this user's directory
        if ($userDir && str_starts_with(realpath($path) ?: '', $userDir) && file_exists($path)) {
            unlink($path);
        }

        return $response->withHeader('Location', '/upload')->withStatus(302);
    }
}