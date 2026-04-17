<?php
require_once 'BaseModel.php';

class UserModel extends BaseModel {
    protected $table = 'users';

    /**
     * Tìm người dùng theo username (Dùng cho cả Đăng ký check trùng và Đăng nhập)
     */
    public function getByUsername(string $username): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $result = $this->fetchOne($sql, [':username' => $username]);
        return $result ?: null;
    }

    /**
     * Đăng ký người dùng mới
     * Đã bổ sung: fullname, phone, address và tự động mã hóa password
     */
    public function register(array $data): bool {
        $sql = "INSERT INTO {$this->table} (username, password_hash, fullname, email, phone, address, role) 
                VALUES (:username, :password_hash, :fullname, :email, :phone, :address, :role)";

        return (bool)$this->prepareStmt($sql, [
            ':username'      => $data['username'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT), // Luôn mã hóa ở đây
            ':fullname'      => $data['fullname'] ?? null,
            ':email'         => $data['email'],
            ':phone'         => $data['phone'] ?? null,
            ':address'       => $data['address'] ?? null,
            ':role'          => $data['role'] ?? 'user' // Mặc định là user để phân biệt với admin
        ]);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Hàm kiểm tra đăng nhập chuẩn bảo mật
     */
    public function checkLogin($username, $password) {
        // 1. getByUsername đã trả về Array hoặc null nhờ fetchOne ở BaseModel
        $user = $this->getByUsername($username);

        // 2. Kiểm tra nếu tìm thấy user
        if ($user && is_array($user)) {
            // 3. So khớp mật khẩu băm (Dùng đúng cột password_hash)
            if (password_verify($password, $user['password_hash'])) {
                return $user;
            }
        }
        return false;
    }
    /**
     * Cập nhật thông tin hồ sơ người dùng
     */
    public function updateProfile($data) {
        // Câu lệnh SQL cập nhật các trường thông tin dựa trên ID
        $sql = "UPDATE users 
            SET fullname = :fullname, 
                email = :email, 
                phone = :phone, 
                address = :address 
            WHERE id = :id";

        // Sử dụng phương thức prepareStmt đã có trong BaseModel để thực thi
        return $this->prepareStmt($sql, [
            ':fullname' => $data['fullname'],
            ':email'    => $data['email'],
            ':phone'    => $data['phone'],
            ':address'  => $data['address'],
            ':id'       => $data['id']
        ]);
    }
    public function getByContact($contact) {
        // Tìm theo email HOẶC số điện thoại
        $sql = "SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contact, $contact]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function resetPassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }
}