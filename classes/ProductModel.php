<?php

require_once "BaseModel.php";
require_once "ProductEntity.php";

class ProductModel extends BaseModel
{
    protected string $table = "products";

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        $rows = $this->fetchAll($sql);

        $products = [];
        foreach ($rows as $row) {
            $products[] = new ProductEntity($row);
        }

        return $products;
    }

    public function getById(int $id): ?ProductEntity
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $row = $this->fetchOne($sql, [$id]);

        if (!$row) {
            return null;
        }

        return new ProductEntity($row);
    }

    public function search(string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        $sql = "SELECT * FROM {$this->table}
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
        $product = new ProductEntity($data);

        $errors = $product->validate();
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }

        $data = $product->toArray();

        $sql = "INSERT INTO {$this->table}
                (name, price, description, image, category_id, stock)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->prepareStmt($sql, [
            $data['name'],
            $data['price'],
            $data['description'],
            $data['image'],
            $data['category_id'],
            $data['stock']
        ]);

        return $stmt->rowCount() > 0;
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $product = new ProductEntity($data);

        $errors = $product->validate();
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }

        $data = $product->toArray();

        $sql = "UPDATE {$this->table}
                SET name=?, price=?, description=?, image=?, category_id=?, stock=?
                WHERE id=?";

        $stmt = $this->prepareStmt($sql, [
            $data['name'],
            $data['price'],
            $data['description'],
            $data['image'],
            $data['category_id'],
            $data['stock'],
            $id
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id=?";

        $stmt = $this->prepareStmt($sql, [$id]);

        return $stmt->rowCount() > 0;
    }
}
