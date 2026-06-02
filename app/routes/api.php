<?php

declare(strict_types=1);

use App\Controllers\ProductController;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Product;
use App\Services\ProductService;

$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

$config = require __DIR__ . '/../config/database.php';

AuthMiddleware::handle();

$dsn = sprintf(
    '%s:host=%s;port=%s;dbname=%s;charset=%s',
    $config['driver'],
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS products (
        product_id INT NOT NULL AUTO_INCREMENT,
        product_name VARCHAR(50) NOT NULL,
        price DECIMAL(20,2) DEFAULT 0,
        quantity INT NOT NULL DEFAULT 0,
        amount DECIMAL(20,2) GENERATED ALWAYS AS (price * quantity) STORED,
        description TEXT DEFAULT NULL,
        image VARCHAR(100) DEFAULT NULL,
        rating DECIMAL(10,2) DEFAULT 0,
        create_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        category_id INT DEFAULT NULL,
        PRIMARY KEY (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$controller = new ProductController(new ProductService(new Product($pdo)));

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    if (isset($_POST['_method'])) {
        $method = strtoupper((string) $_POST['_method']);
    } elseif (isset($_GET['_method'])) {
        $method = strtoupper((string) $_GET['_method']);
    }
}
$path = isset($_GET['path']) ? $_GET['path'] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim((string) $path, '/');
$segments = $uri === '' ? [] : explode('/', $uri);

if (isset($segments[0]) && ($segments[0] === 'api' || $segments[0] === 'api.php')) {
    array_shift($segments);
}

if (!isset($segments[0]) || $segments[0] !== 'products') {
    Response::json(['message' => 'Not Found'], 404);
}

$id = isset($segments[1]) ? (int) $segments[1] : null;

switch ($method) {
    case 'GET':
        if ($id === null) {
            $controller->index();
            break;
        }
        $controller->show($id);
        break;
    case 'POST':
        $controller->store();
        break;
    case 'PUT':
        if ($id === null) {
            Response::json(['message' => 'Product ID required'], 400);
        }
        $controller->update($id);
        break;
    case 'DELETE':
        if ($id === null) {
            Response::json(['message' => 'Product ID required'], 400);
        }
        $controller->destroy($id);
        break;
    case 'OPTIONS':
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization');
        http_response_code(204);
        exit;
    default:
        Response::json(['message' => 'Method Not Allowed'], 405);
}
