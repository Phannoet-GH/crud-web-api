<?php

return [
    'driver' => getenv('DB_DRIVER') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3308',
    'database' => getenv('DB_DATABASE') ?: 'sv1112_db',
    'username' => getenv('DB_USERNAME') ?: 'channa',
    'password' => getenv('DB_PASSWORD') ?: '123',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
