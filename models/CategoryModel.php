<?php
require_once 'BaseModel.php';

class CategoryModel extends BaseModel {
    protected $table = 'categories';

    /**
     * Lấy toàn bộ danh mục để hiển thị Menu hoặc Lọc sản phẩm
     */
    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy thông tin danh mục cụ thể
     */
    public function getById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $result = $this->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * Thêm danh mục mới (Admin)
     */
    public function add(array $data): bool {
        $sql = "INSERT INTO {$this->table} (name, description) VALUES (:name, :description)";
        $stmt = $this->prepareStmt($sql, [
            ':name' => $data['name'],
            ':description' => $data['description']
        ]);
        return (bool)$stmt;
    }

    /**
     * Cập nhật danh mục (Admin)
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->table} SET name = :name, description = :description WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->prepareStmt($sql, $data);
        return (bool)$stmt;
    }

    /**
     * Xóa danh mục
     * Lưu ý: Trong thực tế, cần kiểm tra xem có sản phẩm nào thuộc danh mục này không trước khi xóa
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->prepareStmt($sql, [':id' => $id]);
        return (bool)$stmt;
    }
    public function insert($data) {
        try {
            $sql = "INSERT INTO categories (name) VALUES (:name)";
            return $this->prepareStmt($sql, [':name' => $data['name']]);
        } catch (PDOException $e) {
            // Nếu lỗi là do trùng lặp (mã lỗi 23000)
            if ($e->getCode() == 23000) {
                return false; // Trả về false thay vì để hiện màn hình lỗi Fatal
            }
            throw $e; // Nếu là lỗi khác thì vẫn báo lỗi
        }
    }
}