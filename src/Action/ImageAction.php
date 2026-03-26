<?php

namespace Foxxything\CDN\Action;

use Foxxything\CDN\Core\ImageProcessor;
use Foxxything\CDN\Core\PathGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final readonly class ImageAction
{
    private const MIME_MAP = [
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    public function __construct(
        private ImageProcessor  $imageProcessor,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $userDir = PathGuard::resolveUserDir(__DIR__ . '/../../public/uploads');

        if (!$userDir) {
            $this->logger->warning('ImageAction: no user dir', [
                'session_user' => $_SESSION['user']['id'] ?? 'none',
            ]);
            return $response->withStatus(empty($_SESSION['user']) ? 401 : 400);
        }

        $relativePath = $args['filename'];              // e.g. "Hosts/Foxx_Pinkerton.jpg"
        $filename     = basename($relativePath);        // e.g. "Foxx_Pinkerton.jpg"
        $path         = $userDir . '/' . $relativePath; // full path on disk

        $this->logger->debug('ImageAction: request', [
            'relativePath' => $relativePath,
            'filename'     => $filename,
            'path'         => $path,
            'exists'       => file_exists($path),
            'query'        => $request->getQueryParams(),
        ]);

        if (!file_exists($path)) {
            $this->logger->warning('ImageAction: file not found', [
                'path' => $path,
            ]);
            return $response->withStatus(404);
        }

        $ext         = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contentType = self::MIME_MAP[$ext] ?? 'application/octet-stream';

        $this->logger->debug('ImageAction: serving file', [
            'filename'     => $filename,
            'ext'          => $ext,
            'content_type' => $contentType,
            'size_bytes'   => filesize($path),
        ]);

        $params = $request->getQueryParams();
        $size   = isset($params['size']) ? (int) $params['size'] : null;

        if ($size !== null) {
            $this->logger->debug('ImageAction: resize requested', [
                'filename' => $filename,
                'size'     => $size,
            ]);

            $data = $this->imageProcessor->resize($path, $size);

            if ($data === null) {
                $this->logger->warning('ImageAction: invalid resize size', [
                    'filename'  => $filename,
                    'requested' => $size,
                ]);
                $sizes = $this->imageProcessor->getAllowedSizes();
                $response->getBody()->write(
                    "Invalid size. Must be between {$sizes['min']} and {$sizes['max']}px."
                );
                return $response->withStatus(400)->withHeader('Content-Type', 'text/plain');
            }

            $this->logger->debug('ImageAction: resize successful', [
                'filename'     => $filename,
                'size'         => $size,
                'output_bytes' => strlen($data),
            ]);

            $response->getBody()->write($data);
            return $response
                ->withHeader('Content-Type', $contentType)
                ->withHeader('Cache-Control', 'public, max-age=86400');
        }

        // Serve original
        $response->getBody()->write(file_get_contents($path));
        return $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Cache-Control', 'public, max-age=86400');
    }
}