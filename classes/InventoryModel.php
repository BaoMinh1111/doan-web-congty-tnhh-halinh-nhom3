<?php

require_once 'InventoryEntity.php';

/**
 * Class InventoryModel
 *
 * Lớp Model thao tác với bảng inventory trong database.
 * Chỉ xử lý truy vấn DB, không chứa business logic.
 *
 * @package App\Models
 */
class InventoryModel
{
    // ================= THUỘC TÍNH =================

    private mysqli $conn;
    private string $table = 'inventory';


    // ================= CONSTRUCTOR =================

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }


    // ================= FIND =================

    /**
     * Tìm tồn kho theo product_id
     */
    public function findByProductId(int $productId): ?InventoryEntity
    {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            return null;
        }

        return new InventoryEntity($result);
    }


    // ================= INSERT =================

    /**
     * Thêm tồn kho mới
     */
    public function insert(InventoryEntity $inventory): int
    {
        $errors = $inventory->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' | ', $errors));
        }

        $data = $inventory->toArray();

        $sql = "INSERT INTO {$this->table} (product_id, stock, updated_at)
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iis",
            $data['product_id'],
            $data['stock'],
            $data['updated_at']
        );

        $stmt->execute();

        return $this->conn->insert_id;
    }


    // ================= UPDATE =================

    /**
     * Cập nhật tồn kho theo product_id
     */
    public function update(InventoryEntity $inventory): bool
    {
        $errors = $inventory->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' | ', $errors));
        }

        $data = $inventory->toArray();

        $sql = "UPDATE {$this->table}
                SET stock = ?, updated_at = ?
                WHERE product_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "isi",
            $data['stock'],
            $data['updated_at'],
            $data['product_id']
        );

        return $stmt->execute();
    }


    // ================= INCREASE =================

    /**
     * Tăng tồn kho
     */
    public function increaseStock(int $productId, int $amount): bool
    {
        $inventory = $this->findByProductId($productId);

        if (!$inventory) {
            return false;
        }

        $inventory->increaseStock($amount);

        return $this->update($inventory);
    }


    // ================= DECREASE =================

    /**
     * Giảm tồn kho
     */
    public function decreaseStock(int $productId, int $amount): bool
    {
        $inventory = $this->findByProductId($productId);

        if (!$inventory) {
            return false;
        }

        $inventory->decreaseStock($amount);

        return $this->update($inventory);
    }


    // ================= CHECK STOCK =================

    /**
     * Kiểm tra đủ hàng không
     */
    public function hasStock(int $productId, int $quantity): bool
    {
        $inventory = $this->findByProductId($productId);

        if (!$inventory) {
            return false;
        }

        return $inventory->getStock() >= $quantity;
    }
}
