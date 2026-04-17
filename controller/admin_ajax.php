<?php
require_once 'AdminController.php';
$admin = new AdminController();

if ($_GET['action'] === 'update_status') {
    $success = $admin->updateOrderStatus($_POST['id'], $_POST['status']);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Cập nhật trạng thái thành công!' : 'Có lỗi xảy ra.'
    ]);
}