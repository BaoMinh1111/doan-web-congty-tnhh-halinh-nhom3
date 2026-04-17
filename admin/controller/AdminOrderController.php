<?php
class AdminOrderController extends BaseController {

    private $orderModel;

    public function __construct() {
        parent::__construct();
        if (!isset($_SESSION['user']) || trim($_SESSION['user']['role']) !== 'admin') {
            header("Location: index.php?controller=auth&action=login");
            exit();
        }
        $this->orderModel = new OrderModel();
    }

    // Danh sách tất cả đơn hàng
    public function index() {
        $orders = $this->orderModel->getAllOrders();
        require_once __DIR__ . '/../views/order/index.php';
    }

    // Chi tiết 1 đơn hàng
    public function detail($id) {
        $order = $this->orderModel->getById($id);             // ✅ đổi từ getOrderById
        $orderDetails = $this->orderModel->getItemsByOrderId($id); // ✅ đổi từ getOrderDetails
        require_once __DIR__ . '/../views/order/detail.php';  // ✅ fix đường dẫn
    }

    // Cập nhật trạng thái
    public function updateStatus() {
        $id     = $_POST['order_id'];
        $status = $_POST['status'];

        $allowed = ['pending', 'processing', 'shipped', 'delivered'];
        if (!in_array($status, $allowed)) {
            header("Location: index.php?controller=order&action=index");
            exit();
        }

        $this->orderModel->updateStatus($id, $status);
        // ✅ Fix redirect đúng với routing của em
        header("Location: index.php?controller=order&action=index");
        exit();
    }
}
