<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Product
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $statement = $this->pdo->query('SELECT * FROM products ORDER BY product_id DESC');
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE product_id = :id');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch(PDO::FETCH_ASSOC);
        return $product === false ? null : $product;
    }

    public function create($data)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (
                product_name,
                price,
                quantity,
                description,
                image,
                rating,
                create_date,
                category_id
            ) VALUES (
                :product_name,
                :price,
                :quantity,
                :description,
                :image,
                :rating,
                :create_date,
                :category_id
            )'
        );

        $createDate = isset($data['create_date']) && $data['create_date'] !== ''
            ? $data['create_date']
            : date('Y-m-d H:i:s');

        $statement->execute([
            'product_name' => $data['product_name'],
            'price' => isset($data['price']) ? $data['price'] : 0,
            'quantity' => isset($data['quantity']) ? $data['quantity'] : 0,
            'description' => isset($data['description']) ? $data['description'] : '',
            'image' => isset($data['image']) ? $data['image'] : null,
            'rating' => isset($data['rating']) ? $data['rating'] : 0,
            'create_date' => $createDate,
            'category_id' => isset($data['category_id']) ? $data['category_id'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update($id, $data)
    {
        $statement = $this->pdo->prepare(
            'UPDATE products SET
                product_name = :product_name,
                price = :price,
                quantity = :quantity,
                description = :description,
                image = :image,
                rating = :rating,
                create_date = :create_date,
                category_id = :category_id
            WHERE product_id = :id'
        );

        $createDate = isset($data['create_date']) && $data['create_date'] !== ''
            ? $data['create_date']
            : date('Y-m-d H:i:s');

        return $statement->execute([
            'product_name' => $data['product_name'],
            'price' => isset($data['price']) ? $data['price'] : 0,
            'quantity' => isset($data['quantity']) ? $data['quantity'] : 0,
            'description' => isset($data['description']) ? $data['description'] : '',
            'image' => isset($data['image']) ? $data['image'] : null,
            'rating' => isset($data['rating']) ? $data['rating'] : 0,
            'create_date' => $createDate,
            'category_id' => isset($data['category_id']) ? $data['category_id'] : null,
            'id' => $id,
        ]);
    }

    public function delete($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE product_id = :id');
        return $statement->execute(['id' => $id]);
    }

    public function search($query)
    {
        $query = trim($query);
        $searchTerm = '%' . $query . '%';
        
        if (is_numeric($query)) {
            // Search by ID first
            $statement = $this->pdo->prepare('SELECT * FROM products WHERE product_id = :id LIMIT 1');
            $statement->execute(['id' => (int) $query]);
            $product = $statement->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                return [$product];
            }
        }
        
        // Search by name (case-insensitive)
        $statement = $this->pdo->prepare(
            'SELECT * FROM products WHERE LOWER(product_name) LIKE LOWER(:name) ORDER BY product_id DESC'
        );
        $statement->execute(['name' => $searchTerm]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function paginate($limit = 10, $offset = 0)
    {
        $limit = max(1, min((int) $limit, 100));
        $offset = max(0, (int) $offset);
        $statement = $this->pdo->prepare(
            'SELECT * FROM products ORDER BY product_id DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function byCharacter($character, $limit = 10, $offset = 0)
    {
        $searchTerm = $character . '%';
        $limit = max(1, min((int) $limit, 100));
        $offset = max(0, (int) $offset);
        $statement = $this->pdo->prepare(
            'SELECT * FROM products WHERE product_name LIKE :name ORDER BY product_id DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':name', $searchTerm);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function startFrom($startId, $limit = 10)
    {
        $startId = max(1, (int) $startId);
        $limit = max(1, min((int) $limit, 100));
        $statement = $this->pdo->prepare(
            'SELECT * FROM products WHERE product_id >= :id ORDER BY product_id ASC LIMIT :limit'
        );
        $statement->bindValue(':id', $startId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
