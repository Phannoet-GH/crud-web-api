<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;

class AuthMiddleware
{
    public static function handle()
    {
        $apiKey = getenv('API_KEY') ?: '';
        if ($apiKey === '') {
            return;
        }

        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $authorization = $_SERVER['HTTP_X_API_KEY'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        } else {
            $authorization = '';
        }
        if (trim($authorization) !== $apiKey) {
            Response::json(['message' => 'Unauthorized'], 401);
        }
    }
}
