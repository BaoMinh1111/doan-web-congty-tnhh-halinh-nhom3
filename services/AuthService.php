<?php
class AuthService {
    private UserModel $userModel;

    public function __construct(UserModel $um) {
        $this->userModel = $um;
    }

    /**
     * Xử lý logic đăng nhập: Kiểm tra username -> Verify mật khẩu -> Lưu Session [cite: 269]
     */
    public function login(string $username, string $password): array {
        $userData = $this->userModel->getByUsername($username);
        
        if (!$userData) {
            return ['success' => false, 'message' => 'Người dùng không tồn tại.'];
        }

        // Kiểm tra mật khẩu (so sánh với mã hash trong DB) [cite: 185]
        if (password_verify($password, $userData['password_hash'])) {
            // Lưu thông tin vào Session để giữ trạng thái đăng nhập 
            $_SESSION['user'] = [
                'id'       => $userData['id'],
                'username' => $userData['username'],
                'fullname' => $userData['fullname'], // Quan trọng để hiển thị tên
                 'role'     => $userData['role'],
                 'email'    => $userData['email']    // Để hiện trong Profile Card
];
            return ['success' => true, 'user' => $_SESSION['user']];
        }

        return ['success' => false, 'message' => 'Mật khẩu không chính xác.'];
    }

    /**
     * Kiểm tra xem người dùng đã đăng nhập chưa [cite: 271]
     */
    public function checkSession(): bool {
        return isset($_SESSION['user']);
    }

    /**
     * Đăng xuất [cite: 314]
     */
    public function logout(): void {
        unset($_SESSION['user']);
        session_destroy();
    }
}