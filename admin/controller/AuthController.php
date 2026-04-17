<?php
// Đảm bảo session luôn được khởi động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../BaseController.php';   // Điều chỉnh đường dẫn nếu cần
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../services/AuthService.php';

class AuthController extends BaseController {
    private $authService;

    public function __construct() {
        parent::__construct();
        $this->authService = new AuthService(new UserModel());
    }

    /**
     * Hiển thị trang Đăng nhập Admin
     */
    public function login() {
        if (SessionHelper::isLoggedIn()) {
            // Nếu đã login thì chuyển về dashboard admin
            $this->redirect('index.php?controller=admin&action=dashboard'); // hoặc đường dẫn admin của bạn
        }

        $this->renderView('admin/auth/login', [   // View riêng của admin
            'pageTitle' => 'Đăng nhập Admin - Hà Linh Tech'
        ]);
    }

    /**
     * Xử lý đăng nhập Admin (POST)
     */
    public function postLogin() {
        $username = ValidatorHelper::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($username, $password);

        if ($result['success']) {
            $user = $_SESSION['user'] ?? null;

            // Kiểm tra quyền Admin
            if ($user && in_array($user['role'] ?? '', ['admin', 'super_admin'])) {
                // Restore cart nếu cần (thường admin ít dùng)
                $userId = $user['id'] ?? null;
                if ($userId) {
                    $backupKey = 'cart_backup_' . $userId;
                    if (isset($_SESSION[$backupKey])) {
                        $_SESSION['cart'] = $_SESSION[$backupKey];
                        unset($_SESSION[$backupKey]);
                    }
                }

                $this->redirect('index.php?controller=admin&action=dashboard');
            } else {
                // Không phải admin → đăng xuất và báo lỗi
                session_destroy();
                $this->renderView('admin/auth/login', [
                    'pageTitle' => 'Đăng nhập Admin - Hà Linh Tech',
                    'error' => 'Bạn không có quyền truy cập khu vực Admin!'
                ]);
            }
        } else {
            $this->renderView('admin/auth/login', [
                'pageTitle' => 'Đăng nhập Admin - Hà Linh Tech',
                'error' => $result['message']
            ]);
        }
    }

    /**
     * Đăng xuất Admin
     */
    public function logout() {
        session_destroy();
        header("Location: index.php?controller=admin&action=auth&method=login"); // hoặc đường dẫn login admin của bạn
        exit();
    }
}
