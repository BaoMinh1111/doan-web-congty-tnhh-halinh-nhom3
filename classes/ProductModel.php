<?php

require_once "BaseModel.php";
require_once "ProductEntity.php";

class ProductModel extends BaseModel
{
    protected string $table = "products";

    public function getAll(): array
    {
        $sql = "SELECT * FROM products ORDER BY id DESC";
        $rows = $this->fetchAll($sql);

        $products = [];
        foreach ($rows as $row) {
            $products[] = new ProductEntity($row);
        }

        return $products;
    }

    public function getById(int $id): ?ProductEntity
    {
        $sql = "SELECT * FROM products WHERE id = ?";
        $row = $this->fetchOne($sql, [$id]);

        if (!$row) {
            return null;
        }

        return new ProductEntity($row);
    }

    public function search(string $keyword): array
    {
        $sql = "SELECT * FROM products 
                WHERE name LIKE ? OR description LIKE ?";

        $rows = $this->fetchAll($sql, [
            "%$keyword%",
            "%$keyword%"
        ]);

        $products = [];
        foreach ($rows as $row) {
            $products[] = new ProductEntity($row);
        }

        return $products;
    }

    public function add(array $data): bool
    {
        $sql = "INSERT INTO products 
                (name, price, description, image, category_id, stock)
                VALUES (?, ?, ?, ?, ?, ?)";

        return $this->prepareStmt($sql, [
            $data['name'],
            $data['price'],
            $data['description'],
            $data['image'],
            $data['category_id'],
            $data['stock']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE products 
                SET name=?, price=?, description=?, image=?, category_id=?, stock=?
                WHERE id=?";

        return $this->prepareStmt($sql, [
            $data['name'],
            $data['price'],
            $data['description'],
            $data['image'],
            $data['category_id'],
            $data['stock'],
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM products WHERE id = ?";
        return $this->prepareStmt($sql, [$id]);
    }
}
