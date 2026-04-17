<?php
require_once 'UserEntity.php';

class AdminEntity extends UserEntity {
    private string $adminLevel; // 'super_admin' hoặc 'moderator'
    private string $lastLogin;

    public function __construct(array $data) {
        parent::__construct($data); // Kế thừa từ UserEntity (id, username, role...)
        $this->adminLevel = $data['admin_level'] ?? 'moderator';
        $this->lastLogin = $data['last_login'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Kiểm tra quyền thực hiện các tác vụ nhạy cảm (như xóa dữ liệu)
     */
    public function canManageSystem(): bool {
        return $this->getRole() === 'admin' && $this->adminLevel === 'super_admin';
    }

    // Getters
    public function getAdminLevel() { return $this->adminLevel; }
    public function getLastLogin() { return $this->lastLogin; }
}