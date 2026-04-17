<?php
// Đảm bảo session luôn được khởi động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../services/AuthService.php';

class AuthController extends BaseController {
    private $authService;

    public function __construct() {
        parent::__construct();
        $this->authService = new AuthService(new UserModel());
    }

    /**
     * Hiển thị trang Đăng nhập
     */
    public function login() {
        if (SessionHelper::isLoggedIn()) {
            $this->redirect('index.php');
        }

        $this->renderView('auth/login', [
            'pageTitle' => 'Đăng nhập - Hà Linh Tech'
        ]);
    }

    /**
     * Xử lý đăng nhập (POST)
     */
    public function postLogin() {
        $username = ValidatorHelper::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($username, $password);

        if ($result['success']) {
            // Restore cart sau khi đăng nhập
            $userId = $_SESSION['user']['id'] ?? null;

            if ($userId) {
                $backupKey = 'cart_backup_' . $userId;
                if (isset($_SESSION[$backupKey])) {
                    $_SESSION['cart'] = $_SESSION[$backupKey];
                    unset($_SESSION[$backupKey]);
                }
            }

            $this->redirect('index.php');
        } else {
            $this->renderView('auth/login', [
                'pageTitle' => 'Đăng nhập - Hà Linh Tech',
                'error' => $result['message']
            ]);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout() {
        $cartBackup = $_SESSION['cart'] ?? [];
        $userId = $_SESSION['user']['id'] ?? null;

        session_destroy();

        session_start(); // Khởi động session mới
        if ($userId) {
            $_SESSION['cart_backup_' . $userId] = $cartBackup;
        }

        header("Location: index.php");
        exit();
    }

    /**
     * Hiển thị trang Đăng ký
     */
    public function register() {
        $this->renderView('auth/register', [
            'pageTitle' => 'Đăng ký tài khoản - Hà Linh Tech'
        ]);
    }

    /**
     * Xử lý đăng ký (POST)
     */
    public function postRegister() {
        $data = ValidatorHelper::sanitize($_POST);

        if ($data['password'] !== ($data['confirm_password'] ?? '')) {
            return $this->renderView('auth/register', [
                'pageTitle' => 'Đăng ký tài khoản - Hà Linh Tech',
                'error' => 'Mật khẩu xác nhận không khớp!'
            ]);
        }

        $userModel = new UserModel();

        if ($userModel->getByUsername($data['username'])) {
            return $this->renderView('auth/register', [
                'pageTitle' => 'Đăng ký tài khoản - Hà Linh Tech',
                'error' => 'Tên đăng nhập này đã được sử dụng!'
            ]);
        }

        $userData = [
            'username' => $data['username'],
            'password' => $data['password'],
            'fullname' => $data['fullname'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'address'  => $data['address'] ?? null,
            'role'     => 'user'
        ];

        $success = $userModel->register($userData);

        if ($success) {
            $this->redirect('index.php?controller=auth&action=login&msg=success');
        } else {
            return $this->renderView('auth/register', [
                'pageTitle' => 'Đăng ký tài khoản - Hà Linh Tech',
                'error' => 'Có lỗi xảy ra trong quá trình tạo tài khoản!'
            ]);
        }
    }

    /**
     * Social Login (Google / Facebook)
     */
    public function socialLogin() {
        $provider = $_GET['provider'] ?? 'google';

        if ($provider === 'google') {
            $clientId = "709371583573-6c9tsds3cn8igkdit9n9m54abe81ji48.apps.googleusercontent.com";

            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $redirectUri = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace('/index.php', '', $_SERVER['PHP_SELF']) . "/index.php?controller=auth&action=googleCallback";

            $params = [
                'client_id'     => $clientId,
                'redirect_uri'  => $redirectUri,
                'response_type' => 'code',
                'scope'         => 'email profile',
                'access_type'   => 'online',
                'prompt'        => 'select_account'
            ];

            $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);
            header("Location: $url");
            exit();
        }
        elseif ($provider === 'facebook') {
            $appId = "921060380727635";
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $redirectUri = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace('/index.php', '', $_SERVER['PHP_SELF']) . "/index.php?controller=auth&action=facebookCallback";

            $url = "https://www.facebook.com/v12.0/dialog/oauth?" . http_build_query([
                    'client_id' => $appId,
                    'redirect_uri' => $redirectUri,
                    'scope' => 'public_profile',
                    'response_type' => 'code'
                ]);

            header("Location: $url");
            exit();
        }
    }

    public function googleCallback() {
        // Giả lập (bạn có thể thay bằng code thật sau)
        if (isset($_GET['code'])) {
            $googleUser = [
                'id'       => 'google_' . time(),
                'username' => 'Google_User_' . rand(100, 999),
                'role'     => 'customer',
                'email'    => 'user@gmail.com'
            ];
            SessionHelper::set('user', $googleUser);
            $this->redirect('index.php');
        } else {
            $this->redirect('index.php?controller=auth&action=login&error=google_cancelled');
        }
    }

    public function facebookCallback() {
        if (isset($_GET['code'])) {
            $fbUser = [
                'id'       => 'fb_' . time(),
                'username' => 'FB_User_' . rand(100, 999),
                'role'     => 'customer'
            ];
            SessionHelper::set('user', $fbUser);
            $this->redirect('index.php');
        } else {
            $this->redirect('index.php?controller=auth&action=login&error=fb_failed');
        }
    }

    // ==================== QUÊN MẬT KHẨU ====================
    public function forgotPassword() {
        $this->renderView('auth/forgot_password', [
            'pageTitle' => 'Quên mật khẩu - Hà Linh Tech'
        ]);
    }

    public function postForgotPassword() {
        $contact = ValidatorHelper::sanitize($_POST['contact'] ?? '');
        $userModel = new UserModel();
        $user = $userModel->getByContact($contact);

        if ($user) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = [
                'code'    => $otp,
                'user_id' => $user['id'],
                'expire'  => time() + 300
            ];

            $msg = "Mã OTP của bạn là: $otp (Hiệu lực 5 phút)";

            $this->renderView('auth/verify_otp', [
                'pageTitle' => 'Xác nhận OTP',
                'success'   => "Hệ thống đã gửi mã xác thực đến $contact. $msg",
                'contact'   => $contact
            ]);
        } else {
            $this->renderView('auth/forgot_password', [
                'pageTitle' => 'Quên mật khẩu',
                'error'     => 'Thông tin liên lạc không tồn tại trong hệ thống!'
            ]);
        }
    }

    public function postVerifyOTP() {
        $otpInput = $_POST['otp'] ?? '';
        $sessionOtp = $_SESSION['reset_otp'] ?? null;

        if ($sessionOtp && $otpInput == $sessionOtp['code'] && time() < $sessionOtp['expire']) {
            $this->renderView('auth/reset_password', [
                'pageTitle' => 'Đặt lại mật khẩu mới'
            ]);
        } else {
            $this->renderView('auth/verify_otp', [
                'pageTitle' => 'Xác nhận OTP',
                'error'     => 'Mã OTP không chính xác hoặc đã hết hạn!'
            ]);
        }
    }

    public function postResetPassword() {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $sessionOtp = $_SESSION['reset_otp'] ?? null;

        if (!$sessionOtp) {
            $this->redirect('index.php?controller=auth&action=login');
        }

        if ($password !== $confirm) {
            return $this->renderView('auth/reset_password', [
                'error' => 'Mật khẩu xác nhận không khớp!'
            ]);
        }

        $userModel = new UserModel();
        $success = $userModel->resetPassword($sessionOtp['user_id'], $password);

        if ($success) {
            unset($_SESSION['reset_otp']);
            $this->redirect('index.php?controller=auth&action=login&msg=reset_success');
        } else {
            $this->renderView('auth/reset_password', [
                'error' => 'Có lỗi xảy ra, vui lòng thử lại!'
            ]);
        }
    }
}