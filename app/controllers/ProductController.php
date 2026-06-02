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

        if ($this->service->get($id) === null) {
            Response::json(['message' => 'Product not found'], 404);
        }

        $this->service->update($id, $data);
        Response::json($this->service->get($id));
    }

    public function destroy($id)
    {
        if ($this->service->get($id) === null) {
            Response::json(['message' => 'Product not found'], 404);
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
