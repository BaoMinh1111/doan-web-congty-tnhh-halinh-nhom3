<?php
require_once 'BaseModel.php';

class CustomerModel extends BaseModel {
    protected $table = 'customers';

    /**
     * Lấy hồ sơ khách hàng theo User ID (dùng khi khách đã đăng nhập)
     */
    public function getByUserId(int $userId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $result = $this->fetchOne($sql, [':user_id' => $userId]);
        return $result ?: null;
    }

    /**
     * Tạo hồ sơ khách hàng mới (thường gọi ngay sau khi User đăng ký thành công)
     */
    public function createProfile(array $data): bool {
        $sql = "INSERT INTO {$this->table} (user_id, full_name, phone, address) 
                VALUES (:user_id, :full_name, :phone, :address)";
        return (bool)$this->prepareStmt($sql, [
            ':user_id'   => $data['user_id'],
            ':full_name' => $data['full_name'],
            ':phone'     => $data['phone'] ?? '',
            ':address'   => $data['address'] ?? ''
        ]);
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(int $userId, array $data): bool {
        $sql = "UPDATE {$this->table} 
                SET full_name = :full_name, phone = :phone, address = :address 
                WHERE user_id = :user_id";
        return (bool)$this->prepareStmt($sql, [
            ':full_name' => $data['full_name'],
            ':phone'     => $data['phone'],
            ':address'   => $data['address'],
            ':user_id'   => $userId
        ]);
    }
}