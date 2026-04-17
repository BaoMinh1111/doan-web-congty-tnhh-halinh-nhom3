<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'bootstrap.php';

// 1. Lấy controller & action
$controller = $_GET['controller'] ?? 'home';
$action     = $_GET['action'] ?? 'index';

// 2. Format tên class
$controllerName = ucfirst($controller) . 'Controller';
$actionName     = $action;

// ===== ADMIN ROUTING =====
$isAdmin = isset($_GET['admin']) && $_GET['admin'] === '1';

if ($isAdmin) {
    // Chỉ admin mới vào được
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: ' . URL_ROOT . '?controller=auth&action=login');
        exit;
    }

    $adminControllerName = 'Admin' . ucfirst($controller) . 'Controller';
    $adminControllerPath = __DIR__ . '/admin/controller/' . $adminControllerName . '.php';

    if (!file_exists($adminControllerPath)) {
        http_response_code(404);
        die("❌ Không tìm thấy Admin Controller: $adminControllerName");
    }

    require_once $adminControllerPath;
    $controllerObject = new $adminControllerName();

    $id = $_REQUEST['id'] ?? $_REQUEST['order_id'] ?? null;

    if (!method_exists($controllerObject, $actionName)) {
        http_response_code(404);
        die("❌ Không tìm thấy action: $actionName");
    }

    try {
        $id !== null ? $controllerObject->$actionName($id) : $controllerObject->$actionName();
    } catch (Throwable $e) {
        echo "<pre>❌ Lỗi: " . $e->getMessage() . "</pre>";
    }
    exit; // Dừng, không chạy tiếp routing thường
}
// ===== END ADMIN ROUTING =====

// 3. Đường dẫn controller
$controllerPath = __DIR__ . '/controller/' . $controllerName . '.php';

// 4. Kiểm tra file tồn tại
if (!file_exists($controllerPath)) {
    http_response_code(404);
    die("❌ Không tìm thấy Controller: $controllerName");
}

require_once $controllerPath;

// 5. Kiểm tra class tồn tại
if (!class_exists($controllerName)) {
    http_response_code(404);
    die("❌ Class $controllerName không tồn tại");
}

// 6. Khởi tạo controller
$controllerObject = new $controllerName();

// 7. Kiểm tra method hợp lệ
if (!method_exists($controllerObject, $actionName)) {
    http_response_code(404);
    die("❌ Không tìm thấy action: $actionName");
}

// 8. Lấy ID linh hoạt (fix bug của bạn 👍)
$id = $_REQUEST['id'] ?? $_REQUEST['product_id'] ?? null;

// 9. Gọi action
try {
    if ($id !== null) {
        $controllerObject->$actionName($id);
    } else {
        $controllerObject->$actionName();
    }
} catch (Throwable $e) {
    // Debug khi dev
    echo "<pre>";
    echo "❌ Lỗi hệ thống:\n";
    echo $e->getMessage();
    echo "</pre>";
}