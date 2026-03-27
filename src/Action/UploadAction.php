<?php

namespace Foxxything\CDN\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final readonly class UploadAction
{
    public function __construct(
        private PhpRenderer $view,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        if (empty($_SESSION['user'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $uploads = $this->getUploads();

        return $this->view->render($response, 'upload.php', [
            'user' => $_SESSION['user'],
            'uploads' => $uploads,
        ]);
    }

    private function getUploads(): array
    {
        $userId = $_SESSION['user']['id'];
        $dir = __DIR__ . '/../../public/uploads/' . $userId;
        if (!is_dir($dir)) return [];

        $baseUrl = $_ENV['APP_BASE_URL'] ?? 'http://localhost:8080';

        return array_values(array_filter(
            array_map(fn($f) => [
                'filename' => $f,
                'url' => $baseUrl . '/image/' . $userId . '/' . $f . '?size=256',
                'cdn' => $baseUrl . '/image/' . $userId . '/' . $f,
            ], scandir($dir)),
            fn($f) => !empty($f['filename'])
                && !in_array($f['filename'], ['.', '..'])
                && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f['filename'])
        ));
    }
}