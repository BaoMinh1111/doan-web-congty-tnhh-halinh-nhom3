<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';

class UserController extends BaseController {
    private $userModel;

    public function __construct() {
        parent::__construct();
        if (!isset($_SESSION['user'])) {
            $this->redirect('index.php?controller=auth&action=login');
        }
        $this->userModel = new UserModel();
    }

    public function profile() {
        $userId = $_SESSION['user']['id'];
        $userData = $this->userModel->getById($userId);

        // ✅ Bỏ require header ở đây, để renderView tự xử lý
        $this->renderView('user/profile', [
            'pageTitle' => 'Hồ sơ cá nhân - Hà Linh Tech',
            'user' => $userData
        ]);
    }

    public function edit() {
        $userId = $_SESSION['user']['id'];
        $userData = $this->userModel->getById($userId);

        // ✅ Bỏ require CategoryModel và require header thủ công
        // renderView trong BaseController đã tự inject $categories rồi
        $this->renderView('user/edit', [
            'pageTitle' => 'Chỉnh sửa hồ sơ - Hà Linh Tech',
            'user' => $userData
        ]);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userId = $_SESSION['user']['id'];
            $data = [
                'id' => $userId,
                'fullname' => $_POST['fullname'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address']
            ];

            if ($this->userModel->updateProfile($data)) {
                $_SESSION['user']['fullname'] = $data['fullname'];
                $_SESSION['user']['email'] = $data['email'];
                header('Location: index.php?controller=user&action=profile&success=1');
            } else {
                echo "Có lỗi xảy ra khi cập nhật!";
            }
        }
    }
}