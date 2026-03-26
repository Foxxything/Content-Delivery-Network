<?php

namespace Foxxything\CDN\Core;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Psr\Log\LoggerInterface;

class ImageProcessor
{
    private ImageManager $manager;

    private const MAX_BYTES = 5 * 1024 * 1024; // 5MB
    private const MIN_QUALITY = 10;
    private const START_QUALITY = 85;
    private const QUALITY_STEP = 5;
    private const MIN_SIZE = 16;
    private const MAX_SIZE = 4096;

    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
        $this->manager = new ImageManager(new Driver());
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Compresses an image file in-place until it is under the size limit.
     * If already under the limit, the file is left untouched.
     */
    public function compress(string $path, int $limitBytes = self::MAX_BYTES): void
    {
        if (!file_exists($path) || filesize($path) <= $limitBytes) {
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $image = $this->manager->read($path);

        // PNG compression works differently — quality maps to 0-9 zlib level
        // so we reduce dimensions instead of quality for PNGs
        if ($ext === 'png') {
            $scale = 0.9;
            do {
                $resized = clone $image;
                $resized->scale(width: (int)($image->width() * $scale));
                $encoded = $resized->toPng();
                $scale -= 0.1;
            } while (strlen((string)$encoded) > $limitBytes && $scale >= 0.3);
        } else {
            $quality = self::START_QUALITY;
            do {
                $encoded = $image->toJpeg($quality);
                $quality -= self::QUALITY_STEP;
            } while (strlen((string)$encoded) > $limitBytes && $quality >= self::MIN_QUALITY);
        }

        file_put_contents($path, (string)$encoded);

        $this->logger->info('Image compressed', [
            'path' => $path,
            'format' => $ext,
            'final_size' => filesize($path),
        ]);
    }

    /**
     * Resizes an image to the given pixel width, maintaining aspect ratio.
     * The size must be in the ALLOWED_SIZES whitelist.
     * Returns the resized image as a binary string, or null if the size is not allowed.
     */
    public function resize(string $path, int $size): ?string
    {
        if ($size < self::MIN_SIZE || $size > self::MAX_SIZE) {
            $this->logger->warning('ImageProcessor::resize — size out of range', [
                'requested' => $size,
                'min'       => self::MIN_SIZE,
                'max'       => self::MAX_SIZE,
            ]);
            return null;
        }

        if (!file_exists($path)) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $image = $this->manager->read($path);

        if ($image->width() <= $size) {
            return file_get_contents($path) ?: null;
        }

        $image->scale(width: $size);

        $encoded = match ($ext) {
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            'webp' => $image->toWebp(90),
            default => $image->toJpeg(90), // jpg, jpeg
        };

        $this->logger->info('Image resized', ['path' => $path, 'size' => $size, 'format' => $ext]);

        return (string)$encoded;
    }

    /**
     * Returns the allowed resize sizes for use in validation or documentation.
     *
     * @return int[]
     */
    public function getAllowedSizes(): array
    {
        return ['min' => self::MIN_SIZE, 'max' => self::MAX_SIZE];
    }
}