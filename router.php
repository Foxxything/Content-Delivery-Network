<?php

// If the request is for a real static file, serve it directly
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public' . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Everything else goes through Slim
require __DIR__ . '/public/index.php';