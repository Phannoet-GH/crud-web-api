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
}
