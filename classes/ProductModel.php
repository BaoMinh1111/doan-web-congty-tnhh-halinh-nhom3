<?php

require_once "BaseModel.php";
require_once "ProductEntity.php";

class ProductModel extends BaseModel
{
    protected string $table = "products";


    // ================= GET ALL =================

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        $rows = $this->fetchAll($sql);

        return array_map(fn($row) => new ProductEntity($row), $rows);
    }


    // ================= GET BY ID =================

    public function getById(int $id): ?ProductEntity
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $row = $this->fetchOne($sql, [$id]);

        return $row ? new ProductEntity($row) : null;
    }


    // ================= SEARCH =================

    public function search(string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') return [];

        $sql = "SELECT * FROM {$this->table}
                WHERE name LIKE ? OR description LIKE ?";

        $param = "%$keyword%";

        $rows = $this->fetchAll($sql, [$param, $param]);

        return array_map(fn($row) => new ProductEntity($row), $rows);
    }


    // ================= ADD =================

    /**
     * Trả về ID vừa insert (chuẩn hơn bool)
     */
    public function add(array $data): int
    {
        $product = new ProductEntity($data);

        $errors = $product->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(", ", $errors));
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

        if (!$stmt) {
            throw new RuntimeException("Lỗi khi thêm sản phẩm");
        }

        return (int)$this->pdo->lastInsertId();
    }


    // ================= UPDATE =================

    /**
     * Trả true nếu query chạy thành công (KHÔNG phụ thuộc rowCount)
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID không hợp lệ");
        }

        $data['id'] = $id;
        $product = new ProductEntity($data);

        $errors = $product->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(", ", $errors));
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

        if (!$stmt) {
            throw new RuntimeException("Lỗi khi cập nhật sản phẩm");
        }

        return true; // FIX: không phụ thuộc rowCount
    }


    // ================= DELETE =================

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID không hợp lệ");
        }

        $sql = "DELETE FROM {$this->table} WHERE id=?";
        $stmt = $this->prepareStmt($sql, [$id]);

        if (!$stmt) {
            throw new RuntimeException("Lỗi khi xoá sản phẩm");
        }

        return $stmt->rowCount() > 0;
    }


    // ================= STOCK =================

    /**
     * Giảm tồn kho an toàn
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        if ($productId <= 0 || $quantity <= 0) {
            throw new InvalidArgumentException("Dữ liệu không hợp lệ");
        }

        $sql = "UPDATE {$this->table}
                SET stock = stock - ?
                WHERE id = ? AND stock >= ?";

        $stmt = $this->prepareStmt($sql, [
            $quantity,
            $productId,
            $quantity
        ]);

        if (!$stmt) {
            throw new RuntimeException("Lỗi khi trừ kho");
        }

        return $stmt->rowCount() > 0;
    }


    // ================= TRANSACTION (chuẩn bị cho OrderService) =================

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}
