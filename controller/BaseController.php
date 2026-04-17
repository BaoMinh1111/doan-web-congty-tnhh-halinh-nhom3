<?php
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/ValidatorHelper.php';

// ✅ Sửa tên file cho đúng
require_once __DIR__ . '/../models/CategoryModel.php';

abstract class BaseController {

    public function __construct() {
        SessionHelper::start();
    }

    protected function renderView($view, $data = []) {
        $data['currentUser'] = SessionHelper::get('user');
        $data['isLoggedIn'] = SessionHelper::isLoggedIn();

        // ✅ Sửa tên class thành CategoryModel
        if (!isset($data['categories'])) {
            $categoryModel = new CategoryModel();
            $data['categories'] = $categoryModel->getAll();
        }

        extract($data);

        $viewPath = "views/" . $view . ".php";
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("Lỗi: Không tìm thấy file giao diện $viewPath");
        }
    }

    protected function jsonResponse($status, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ]);
        exit();
    }

    protected function redirect($url) {
        header("Location: $url");
        exit();
    }
}