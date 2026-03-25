<?php

namespace Foxxything\CDN\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\PhpRenderer;

final readonly class UploadPostAction
{
    public function __construct(
        private PhpRenderer $view,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if (empty($_SESSION['user'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $userId    = $_SESSION['user']['id'];
        $uploadDir = __DIR__ . '/../../public/uploads/' . $userId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $body    = $request->getParsedBody();
        $files   = $request->getUploadedFiles();

        // Step 2 — user confirmed overwrite, file is in session temp
        if (isset($body['confirm_overwrite'], $_SESSION['pending_upload'])) {
            $pending  = $_SESSION['pending_upload'];
            unset($_SESSION['pending_upload']);

            if ($body['confirm_overwrite'] === 'yes') {
                rename($pending['tmp'], $uploadDir . '/' . $pending['filename']);
            }
            // 'no' — just discard, redirect back
            return $response->withHeader('Location', '/upload')->withStatus(302);
        }

        // Step 1 — fresh upload
        $uploaded = $files['image'] ?? null;

        $this->logger->info("Uploading files: {$uploaded->getClientFilename()}");

        if (!$uploaded || $uploaded->getError() !== UPLOAD_ERR_OK) {
            return $response->withHeader('Location', '/upload')->withStatus(302);
        }

        $filename = $uploaded->getClientFilename();
        $dest     = $uploadDir . '/' . $filename;

        if (file_exists($dest)) {
            // Stash the file in a system temp location and store path in session
            $tmp = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '_' . $filename;
            $uploaded->moveTo($tmp);

            $_SESSION['pending_upload'] = [
                'tmp'      => $tmp,
                'filename' => $filename,
            ];

            return $this->view->render($response, 'confirm_overwrite.php', [
                'filename' => $filename,
                'user'     => $_SESSION['user'],
            ]);
        }

        $uploaded->moveTo($dest);

        return $response->withHeader('Location', '/upload')->withStatus(302);
    }
}