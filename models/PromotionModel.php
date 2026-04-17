<?php
require_once 'BaseModel.php';

class PromotionModel extends BaseModel {
    protected $table = 'promotions';

    /**
     * Tìm mã giảm giá theo Code
     */
    public function getByCode(string $code): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE code = :code LIMIT 1";
        $result = $this->fetchOne($sql, [':code' => $code]);
        return $result ?: null;
    }

    /**
     * Admin: Thêm mã giảm giá mới
     */
    public function add(array $data): bool {
        $sql = "INSERT INTO {$this->table} (code, discount_value, expiration_date, usage_limit, used_count) 
                VALUES (:code, :discount_value, :expiration_date, :usage_limit, 0)";
        return (bool)$this->prepareStmt($sql, $data);
    }

    /**
     * Cập nhật số lần sử dụng (Nếu không dùng Stored Procedure)
     */
    public function incrementUsedCount(int $id): void {
        $sql = "UPDATE {$this->table} SET used_count = used_count + 1 WHERE id = :id";
        $this->prepareStmt($sql, [':id' => $id]);
    }
}