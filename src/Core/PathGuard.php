<?php

namespace Foxxything\CDN\Core;

final class PathGuard
{
    /**
     * Returns true if the given path resolves to a location
     * inside the allowed base directory.
     */
    public static function isInside(string $path, string $baseDir): bool
    {
        return str_starts_with(realpath($path) ?: $path, $baseDir);
    }

    /**
     * Returns the user's upload directory and verifies it exists.
     * Returns null if the directory doesn't exist yet.
     */
    public static function userDir(string $baseUploadDir, string $userId): ?string
    {
        $dir = realpath($baseUploadDir . '/' . $userId);
        return $dir ?: null;
    }

    /**
     * Resolves the current session user's upload directory.
     * Returns null if not logged in or directory doesn't exist.
     */
    public static function resolveUserDir(string $baseUploadPath): ?string
    {
        if (empty($_SESSION['user']['id'])) {
            return null;
        }

        $userId = $_SESSION['user']['id'];
        return realpath($baseUploadPath . '/' . $userId) ?: null;
    }
}