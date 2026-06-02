<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;

class ProductService
{
    private $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function list()
    {
        return $this->product->all();
    }

    public function get($id)
    {
        return $this->product->find($id);
    }

    public function existsByName(string $name, ?int $excludeId = null)
    {
        return $this->product->existsByName($name, $excludeId);
    }

    public function create($data)
    {
        return $this->product->create($data);
    }

    public function update($id, $data)
    {
        return $this->product->update($id, $data);
    }

    public function delete($id)
    {
        return $this->product->delete($id);
    }

    public function search($query)
    {
        return $this->product->search($query);
    }

    public function paginate($limit = 10, $offset = 0)
    {
        return $this->product->paginate($limit, $offset);
    }

    public function byCharacter($character, $limit = 10, $offset = 0)
    {
        return $this->product->byCharacter($character, $limit, $offset);
    }

    public function startFrom($startId, $limit = 10)
    {
        return $this->product->startFrom($startId, $limit);
    }
}
