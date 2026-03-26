<?php

namespace Foxxything\CDN\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\PhpRenderer;

final readonly class UploadPostAction
{
    // PHP upload error codes mapped to readable messages
    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in form',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload stopped by PHP extension',
    ];

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

        $body  = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        // Step 2 — user confirmed overwrite(s)
        if (isset($body['confirm_overwrite'], $_SESSION['pending_uploads'])) {
            $pendings = $_SESSION['pending_uploads'];
            unset($_SESSION['pending_uploads']);

            if ($body['confirm_overwrite'] === 'yes') {
                foreach ($pendings as $pending) {
                    $this->logger->info('Overwriting file', ['filename' => $pending['filename']]);
                    rename($pending['tmp'], $uploadDir . '/' . $pending['filename']);
                }
            } else {
                foreach ($pendings as $pending) {
                    $this->logger->info('Overwrite cancelled', ['filename' => $pending['filename']]);
                    if (file_exists($pending['tmp'])) {
                        unlink($pending['tmp']);
                    }
                }
            }

            return $response->withHeader('Location', '/upload')->withStatus(302);
        }

        // Step 1 — fresh upload
        $uploaded = $files['images'] ?? [];

        if (!is_array($uploaded)) {
            $uploaded = [$uploaded];
        }

        $this->logger->info('Upload batch received', ['count' => count($uploaded)]);

        $conflicts = [];
        $clean     = [];

        foreach ($uploaded as $index => $file) {
            if (!$file) {
                $this->logger->warning('Null file at index', ['index' => $index]);
                continue;
            }

            $error    = $file->getError();
            $filename = $file->getClientFilename();
            $size     = $file->getSize();
            $mime     = $file->getClientMediaType();

            $this->logger->info('Processing file', [
                'index'    => $index,
                'filename' => $filename,
                'size'     => $size,
                'mime'     => $mime,
                'error'    => $error,
            ]);

            if ($error !== UPLOAD_ERR_OK) {
                $reason = self::UPLOAD_ERRORS[$error] ?? 'Unknown error code ' . $error;
                $this->logger->error('File upload failed', [
                    'filename' => $filename,
                    'error'    => $error,
                    'reason'   => $reason,
                ]);
                continue;
            }

            $dest = $uploadDir . '/' . $filename;

            if (file_exists($dest)) {
                $tmp = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '_' . $filename;
                $this->logger->info('Conflict detected, stashing to temp', [
                    'filename' => $filename,
                    'tmp'      => $tmp,
                ]);
                $file->moveTo($tmp);
                $conflicts[] = ['tmp' => $tmp, 'filename' => $filename];
            } else {
                $this->logger->info('Queuing clean upload', ['filename' => $filename, 'dest' => $dest]);
                $clean[] = ['file' => $file, 'dest' => $dest, 'filename' => $filename];
            }
        }

        foreach ($clean as $item) {
            try {
                $item['file']->moveTo($item['dest']);
                $this->logger->info('File uploaded successfully', ['filename' => $item['filename']]);
            } catch (\Throwable $e) {
                $this->logger->error('moveTo failed', [
                    'filename' => $item['filename'],
                    'dest'     => $item['dest'],
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if (!empty($conflicts)) {
            $_SESSION['pending_uploads'] = $conflicts;

            return $this->view->render($response, 'confirm_overwrite.php', [
                'filenames' => array_column($conflicts, 'filename'),
                'user'      => $_SESSION['user'],
            ]);
        }

        return $response->withHeader('Location', '/upload')->withStatus(302);
    }
}