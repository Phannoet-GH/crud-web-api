<?php

/*
|--------------------------------------------------------------------------
| Simple Router for PHP Built-in Server
|--------------------------------------------------------------------------
|
| Run the project like this:
| php -S localhost:8000 public/router.php
|
| This file has one job:
| - If the browser asks for a real file, show that file.
| - If not, send the request to index.php.
|
*/

if (php_sapi_name() === 'cli-server') {
    // Step 1: Get the page or file name from the URL.
    // Example URL: http://localhost:8000/styles.css
    // Result: /styles.css
    $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($urlPath === false || $urlPath === null) {
        $urlPath = '/';
    }

    // Step 2: Convert the URL path into a real file path.
    // __DIR__ means the folder where this router.php file is located.
    $filePath = __DIR__ . $urlPath;

    // Step 3: If that file exists, let PHP show it directly.
    // This is useful for CSS, JavaScript, images, and favicon files.
    if ($urlPath !== '/' && file_exists($filePath) && is_file($filePath)) {
        return false;
    }
}

// If the request is not a real file, load the main application.
// index.php will handle the web page and API routes.
require __DIR__ . '/index.php';
