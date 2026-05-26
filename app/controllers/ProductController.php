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
        $data = $this->readJsonBody();
        if (empty($data['product_name'])) {
            Response::json(['message' => 'Product name is required'], 422);
        }

        $id = $this->service->create($data);
        $product = $this->service->get($id);
        Response::json($product, 201);
    }

    public function update($id)
    {
        $data = $this->readJsonBody();
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
