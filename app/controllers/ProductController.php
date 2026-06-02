<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\ProductService;

class ProductController
{
    private $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        Response::json($this->service->list());
    }

    public function show($id)
    {
        $product = $this->service->get($id);
        if ($product === null) {
            Response::json(['message' => 'Product not found'], 404);
        }
        Response::json($product);
    }

    public function store()
    {
        $data = $this->readRequestData();
        if (empty($data['product_name'])) {
            Response::json(['message' => 'Product name is required'], 422);
        }

        if ($this->service->existsByName($data['product_name'])) {
            Response::json(['message' => 'Product name already exists'], 422);
        }

        $id = $this->service->create($data);
        $product = $this->service->get($id);
        Response::json($product, 201);
    }

    public function update($id)
    {
        $data = $this->readRequestData();
        if (empty($data['product_name'])) {
            Response::json(['message' => 'Product name is required'], 422);
        }

        $existing = $this->service->get($id);
        if ($existing === null) {
            Response::json(['message' => 'Product not found'], 404);
        }

        if ($this->service->existsByName($data['product_name'], $id)) {
            Response::json(['message' => 'Product name already exists'], 422);
        }

        $existingImage = $existing['image'] ?? null;
        $this->service->update($id, $data);

        $newImage = $data['image'] ?? null;
        if ($existingImage && $existingImage !== $newImage) {
            $this->deleteStoredImage((string) $existingImage);
        }

        Response::json($this->service->get($id));
    }

    public function destroy($id)
    {
        $existing = $this->service->get($id);
        if ($existing === null) {
            Response::json(['message' => 'Product not found'], 404);
        }

        $existingImage = $existing['image'] ?? null;
        if ($existingImage) {
            $this->deleteStoredImage((string) $existingImage);
        }

        $this->service->delete($id);
        Response::json(['message' => 'Product deleted']);
    }

    private function readRequestData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'multipart/form-data') !== false || !empty($_FILES)) {
            $data = $_POST;

            if (isset($data['image']) && $data['image'] === '') {
                $data['image'] = null;
            }

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $data['image'] = $this->storeUploadedImage($_FILES['image']);
            }

            return $data;
        }

        return $this->readJsonBody();
    }

    private function storeUploadedImage(array $image)
    {
        if (!is_uploaded_file($image['tmp_name'])) {
            Response::json(['message' => 'Invalid uploaded image'], 400);
        }

        if ($this->isCloudinaryConfigured()) {
            return $this->uploadToCloudinary($image);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($image['tmp_name']) ?: $image['type'];
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mimeType])) {
            Response::json(['message' => 'Unsupported image type'], 422);
        }

        $extension = $allowed[$mimeType];
        $uploadsDir = __DIR__ . '/../../public/uploads';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
            Response::json(['message' => 'Failed to create uploads directory'], 500);
        }

        $filename = uniqid('img_', true) . '.' . $extension;
        $destination = $uploadsDir . '/' . $filename;

        if (!move_uploaded_file($image['tmp_name'], $destination)) {
            Response::json(['message' => 'Failed to save uploaded image'], 500);
        }

        return 'uploads/' . $filename;
    }

    private function isLocalUploadedImage(string $imagePath): bool
    {
        return trim($imagePath) !== '' && strpos($imagePath, 'uploads/') === 0;
    }

    private function isCloudinaryConfigured(): bool
    {
        return getenv('CLOUDINARY_CLOUD_NAME') && getenv('CLOUDINARY_API_KEY') && getenv('CLOUDINARY_API_SECRET');
    }

    private function uploadToCloudinary(array $image): string
    {
        if (!function_exists('curl_init') || !class_exists('CURLFile')) {
            Response::json(['message' => 'Cloudinary upload requires curl extension'], 500);
        }

        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $apiKey = getenv('CLOUDINARY_API_KEY');
        $apiSecret = getenv('CLOUDINARY_API_SECRET');
        $folder = getenv('CLOUDINARY_UPLOAD_FOLDER') ?: '';
        $timestamp = time();

        $params = ['timestamp' => $timestamp];
        if ($folder !== '') {
            $params['folder'] = $folder;
        }

        ksort($params);
        $signatureString = '';
        foreach ($params as $key => $value) {
            $signatureString .= $key . '=' . $value . '&';
        }
        $signature = sha1(rtrim($signatureString, '&') . $apiSecret);

        $post = [
            'file' => new \CURLFile($image['tmp_name'], $image['type'], $image['name']),
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        if ($folder !== '') {
            $post['folder'] = $folder;
        }

        $url = sprintf('https://api.cloudinary.com/v1_1/%s/image/upload', $cloudName);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($curl);

        if ($response === false) {
            Response::json(['message' => 'Cloudinary request failed', 'error' => curl_error($curl)], 500);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $body = json_decode($response, true);
        if ($httpCode !== 200 || empty($body['secure_url'])) {
            Response::json(['message' => 'Cloudinary upload failed', 'response' => $body], 500);
        }

        return $body['secure_url'];
    }

    private function isCloudinaryUrl(string $imagePath): bool
    {
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        if (!$cloudName) {
            return false;
        }

        return strpos($imagePath, 'res.cloudinary.com/' . $cloudName . '/image/upload') !== false;
    }

    private function deleteStoredImage(string $imagePath): void
    {
        if ($this->isLocalUploadedImage($imagePath)) {
            $filePath = __DIR__ . '/../../public/' . ltrim($imagePath, '/');
            if (is_file($filePath)) {
                @unlink($filePath);
            }
            return;
        }

        if ($this->isCloudinaryUrl($imagePath) && $this->isCloudinaryConfigured()) {
            $this->deleteCloudinaryImage($imagePath);
        }
    }

    private function deleteCloudinaryImage(string $imagePath): void
    {
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $apiKey = getenv('CLOUDINARY_API_KEY');
        $apiSecret = getenv('CLOUDINARY_API_SECRET');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return;
        }

        $pattern = '/res\.cloudinary\.com\/' . preg_quote($cloudName, '/') . '\/image\/upload\/(?:v\d+\/)?(.+)\.[a-zA-Z0-9]+$/';
        if (!preg_match($pattern, $imagePath, $matches)) {
            return;
        }

        $publicId = $matches[1];
        $timestamp = time();
        $params = "public_id={$publicId}&timestamp={$timestamp}";
        $signature = sha1($params . $apiSecret);

        $post = [
            'public_id' => $publicId,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $url = sprintf('https://api.cloudinary.com/v1_1/%s/image/destroy', $cloudName);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_exec($curl);
        curl_close($curl);
    }

    private function deleteUploadedImage(string $imagePath): void
    {
        $filePath = __DIR__ . '/../../public/' . ltrim($imagePath, '/');
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    public function search()
    {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            Response::json(['message' => 'Search query is required'], 422);
        }

        $results = $this->service->search($query);
        Response::json($results);
    }

    public function listByCharacter()
    {
        $character = $_GET['char'] ?? '';
        if (empty($character)) {
            Response::json(['message' => 'Character parameter is required'], 422);
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $results = $this->service->byCharacter($character[0], $limit, $offset);
        Response::json($results);
    }

    public function listPaginated()
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $results = $this->service->paginate($limit, $offset);
        Response::json($results);
    }

    public function listFromId()
    {
        $startId = $_GET['start'] ?? '';
        if (empty($startId) || !is_numeric($startId)) {
            Response::json(['message' => 'Start ID parameter is required and must be numeric'], 422);
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $results = $this->service->startFrom((int) $startId, $limit);
        Response::json($results);
    }

    private function readJsonBody()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Response::json(['message' => 'Invalid JSON body'], 400);
        }
        return $data;
    }
}
