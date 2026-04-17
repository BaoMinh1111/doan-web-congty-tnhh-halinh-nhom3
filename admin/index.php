<?php
session_start();

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../controller/BaseController.php';
require_once __DIR__ . '/../models/OrderModel.php';

$controller = $_GET['controller'] ?? 'product';
$action = $_GET['action'] ?? 'index';

// Kiểm tra quyền
if ($controller !== 'auth') {
    if (!isset($_SESSION['user']) || trim($_SESSION['user']['role']) !== 'admin') {
        header("Location: index.php?controller=auth&action=login");
        exit();
    }
}

// Điều hướng
$controllerName = "Admin" . ucfirst($controller) . "Controller";
$controllerFile = "controller/" . $controllerName . ".php";

if (!file_exists($controllerFile)) {
    $controllerName = ucfirst($controller) . "Controller";
    $controllerFile = "controller/" . $controllerName . ".php";
}

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerObj = new $controllerName();

    // ✅ Lấy id nếu có
    $id = $_REQUEST['id'] ?? $_REQUEST['order_id'] ?? null;

    if ($id !== null) {
        $controllerObj->$action($id);
    } else {
        $controllerObj->$action();
    }
} else {
    die("Controller $controllerName không tồn tại!");
}