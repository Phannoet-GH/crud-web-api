<?php

declare(strict_types=1);

function allowBrowserApiRequests()
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
}

allowBrowserApiRequests();

try {
    $autoloadFile = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($autoloadFile)) {
        require $autoloadFile;
    } else {
        spl_autoload_register(function ($className) {
            $prefix = 'App\\';

            if (strpos($className, $prefix) !== 0) {
                return;
            }

            $relativeClass = substr($className, strlen($prefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            $parts = explode(DIRECTORY_SEPARATOR, $relativePath);

            if (isset($parts[0])) {
                $parts[0] = strtolower($parts[0]);
            }

            $file = __DIR__ . '/../app/' . implode(DIRECTORY_SEPARATOR, $parts);

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    require __DIR__ . '/../app/routes/api.php';
} catch (Throwable $error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'message' => 'API error',
        'error' => $error->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
