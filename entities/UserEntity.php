<?php
class UserEntity {
    private int $id;
    private string $username;
    private string $passwordHash;
    private string $email;
    private string $role; // 'admin' hoặc 'customer' [cite: 55]

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->username = $data['username'] ?? '';
        $this->passwordHash = $data['password_hash'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->role = $data['role'] ?? 'customer'; // [cite: 57]
    }

    /**
     * Kiểm tra dữ liệu người dùng trước khi lưu [cite: 59]
     */
    public function validate(): array {
        $errors = [];
        if (strlen($this->username) < 4) $errors[] = "Tên đăng nhập phải có ít nhất 4 ký tự.";
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
        return $errors;
    }

    /**
     * Mã hóa mật khẩu thuần thành Hash trước khi lưu vào DB 
     */
    public function hashPassword(string $plainPassword): void {
        $this->passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    /**
     * Trả về mảng dữ liệu, ẩn mật khẩu để an toàn [cite: 61]
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role
        ];
    }

    // Getters [cite: 58]
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
}